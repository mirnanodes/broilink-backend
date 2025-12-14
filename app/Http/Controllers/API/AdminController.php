<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use App\Models\FarmConfig;
use App\Models\RequestLog;
use App\Models\IotData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get admin dashboard summary
     */
    public function dashboard()
    {
        $summary = [
            'total_owner' => User::where('role_id', 2)->count(),
            'total_peternak' => User::where('role_id', 3)->count(),
            'total_guest_requests' => RequestLog::where('user_id', 0)->count()
        ];

        $recent = RequestLog::with('user.role')
            ->orderBy('sent_time', 'desc')
            ->limit(3)
            ->get()
            ->map(function($r) {
                return [
                    'username' => $r->user ? $r->user->username : 'Guest',
                    'role' => $r->user && $r->user->role ? $r->user->role->name : '-',
                    'time' => Carbon::parse($r->sent_time)->format('d M Y, H:i'),
                    'request_type' => $r->request_type
                ];
            });

        return response()->json([
            'success' => true,
            'data' => compact('summary', 'recent')
        ]);
    }

    /**
     * Get all users (excluding admin)
     */
    public function getUsers(Request $request)
    {
        $query = User::with('role')->where('role_id', '!=', 1);

        if ($search = $request->search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(function($u) {
            return [
                'user_id' => $u->user_id,
                'username' => $u->username,
                'name' => $u->name,
                'role' => $u->role ? $u->role->name : 'No Role',
                'status' => $u->last_login && Carbon::parse($u->last_login)->diffInDays(now()) < 30 ? 'active' : 'nonaktif',
                'date_joined' => $u->date_joined,
                'last_login' => $u->last_login,
                'email' => $u->email,
                'phone_number' => $u->phone_number
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get single user detail
     */
    public function getUser($id)
    {
        $user = User::with('role')->where('user_id', $id)->firstOrFail();

        // If peternak, get owner info
        $owner_name = null;
        $owner_id = null;
        $farm_id = null;

        if ($user->role_id == 3) { // Peternak
            $farm = Farm::with('owner')->where('peternak_id', $user->user_id)->first();
            if ($farm) {
                $farm_id = $farm->farm_id;
                if ($farm->owner) {
                    $owner_id = $farm->owner->user_id;
                    $owner_name = $farm->owner->name;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role_id' => $user->role_id,
                'role' => $user->role ? $user->role->name : null,
                'farm_id' => $farm_id,
                'owner_id' => $owner_id,
                'owner_name' => $owner_name,
                'status' => $user->status,
                'date_joined' => $user->date_joined,
                'last_login' => $user->last_login,
            ]
        ]);
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,role_id', // roles table primary key is 'role_id'
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required',
            'phone_number' => 'nullable',
            'farm_name' => 'nullable|string', // For Owner role - auto-create farm
            'location' => 'nullable|string'   // Optional farm location
        ]);

        $user = User::create($validated);

        // If creating an Owner (role_id = 2) and farm_name is provided, auto-create farm
        if ($user->role_id == 2 && !empty($validated['farm_name'])) {
            $farm = Farm::create([
                'owner_id' => $user->user_id,
                'farm_name' => $validated['farm_name'],
                'location' => $validated['location'] ?? null,
                'peternak_id' => null,
                'initial_population' => null,
                'initial_weight' => null,
                'farm_area' => null
            ]);

            // Create default farm config for the new farm
            $this->createDefaultConfig($farm->farm_id);

            return response()->json([
                'success' => true,
                'message' => 'Owner and farm created successfully',
                'data' => [
                    'user' => $user,
                    'farm' => $farm
                ]
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        // Use find() instead of findOrFail()
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'role_id' => 'sometimes|exists:roles,role_id', // roles table primary key is 'role_id'
            'username' => 'sometimes|unique:users,username,' . $id . ',user_id',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',user_id',
            'password' => 'sometimes|min:6',
            'name' => 'sometimes',
            'phone_number' => 'nullable',
            'status' => 'sometimes|in:active,inactive'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // REMOVED: farm_id assignment for peternak
        // Peternak assignment is now handled in Manajemen Kandang page
        // via PUT /api/admin/farms/{id}/assign-peternak endpoint

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        // Use find() instead of findOrFail()
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get all farms
     */
    public function getFarms()
    {
        $farms = Farm::with(['owner', 'peternak'])->get()->map(function($f) {
            return [
                'farm_id' => $f->farm_id,
                'farm_name' => $f->farm_name,
                'location' => $f->location,
                'owner' => $f->owner ? $f->owner->name : null,
                'peternak' => $f->peternak ? $f->peternak->name : 'Not Assigned',
                'initial_population' => $f->initial_population,
                'farm_area' => $f->farm_area
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $farms
        ]);
    }

    /**
     * Create new farm
     */
    public function createFarm(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:users,user_id',
            'peternak_id' => 'nullable|exists:users,user_id',
            'farm_name' => 'required',
            'location' => 'nullable',
            'initial_population' => 'nullable|integer',
            'initial_weight' => 'nullable|numeric',
            'farm_area' => 'nullable|numeric'
        ]);

        $farm = Farm::create($validated);

        // Create default farm config
        $this->createDefaultConfig($farm->farm_id);

        return response()->json([
            'success' => true,
            'message' => 'Farm created successfully',
            'data' => $farm
        ], 201);
    }

    /**
     * Get farm configuration
     */
    public function getFarmConfig($id)
    {
        // Use find() instead of findOrFail()
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        $config = $this->getFarmConfigArray($id);

        return response()->json([
            'success' => true,
            'data' => [
                'farm_id' => $farm->farm_id,
                'farm_name' => $farm->farm_name,
                'config' => $config
            ]
        ]);
    }

    /**
     * Update farm configuration
     */
    public function updateFarmConfig(Request $request, $id)
    {
        // Use find() instead of findOrFail()
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'suhu_normal_min' => 'nullable|numeric',
            'suhu_normal_max' => 'nullable|numeric',
            'suhu_kritis_rendah' => 'nullable|numeric',
            'suhu_kritis_tinggi' => 'nullable|numeric',
            'kelembapan_normal_min' => 'nullable|numeric',
            'kelembapan_normal_max' => 'nullable|numeric',
            'kelembapan_kritis_rendah' => 'nullable|numeric',
            'kelembapan_kritis_tinggi' => 'nullable|numeric',
            'amonia_max' => 'nullable|numeric',
            'amonia_kritis' => 'nullable|numeric'
        ]);

        foreach ($validated as $key => $value) {
            FarmConfig::updateOrCreate(
                ['farm_id' => $id, 'parameter_name' => $key],
                ['value' => $value]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Farm configuration updated successfully'
        ]);
    }

    /**
     * Reset farm configuration to default
     */
    public function resetFarmConfig($id)
    {
        // Use find() instead of findOrFail()
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        // Delete existing configs
        FarmConfig::where('farm_id', $id)->delete();

        // Create default configs
        $this->createDefaultConfig($id);

        return response()->json([
            'success' => true,
            'message' => 'Farm configuration reset to default'
        ]);
    }

    /**
     * Upload IoT data from CSV file
     */
    public function uploadIotCsv(Request $request, $id)
    {
        // Validate farm exists
        $farm = Farm::find($id);
        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found'
            ], 404);
        }

        // Validate file
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240' // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));

            // Remove header row
            $header = array_shift($csvData);

            // Expected header: timestamp, temperature, humidity, ammonia
            $expectedHeaders = ['timestamp', 'temperature', 'humidity', 'ammonia'];
            $headerLower = array_map('strtolower', array_map('trim', $header));

            if ($headerLower !== $expectedHeaders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid CSV format. Expected headers: timestamp, temperature, humidity, ammonia'
                ], 422);
            }

            $inserted = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                if (count($row) !== 4) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid number of columns";
                    continue;
                }

                [$timestamp, $temperature, $humidity, $ammonia] = $row;

                // Validate data
                $rowValidator = Validator::make([
                    'timestamp' => trim($timestamp),
                    'temperature' => trim($temperature),
                    'humidity' => trim($humidity),
                    'ammonia' => trim($ammonia)
                ], [
                    'timestamp' => 'required|date',
                    'temperature' => 'required|numeric|between:0,50',
                    'humidity' => 'required|numeric|between:0,100',
                    'ammonia' => 'required|numeric|between:0,100'
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $rowValidator->errors()->all());
                    continue;
                }

                // Insert data
                IotData::create([
                    'farm_id' => $id,
                    'temperature' => (float) trim($temperature),
                    'humidity' => (float) trim($humidity),
                    'ammonia' => (float) trim($ammonia),
                    'data_source' => 'csv_upload',
                    'timestamp' => Carbon::parse(trim($timestamp))
                ]);

                $inserted++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully uploaded $inserted IoT data records",
                'data' => [
                    'inserted' => $inserted,
                    'errors' => $errors,
                    'total_rows' => count($csvData)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing CSV file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all requests
     */
    public function getRequests(Request $request)
    {
        $sortOrder = $request->query('sort', 'desc');
        $order = $sortOrder === 'oldest' ? 'asc' : 'desc';

        $requests = RequestLog::with('user.role')
            ->orderBy('sent_time', $order)
            ->get()
            ->map(function($r) {
                return [
                    'request_id' => $r->request_id,
                    'sender_name' => $r->user ? $r->user->name : $r->sender_name,
                    'username' => $r->user ? $r->user->username : 'Guest',
                    'role' => $r->user && $r->user->role ? $r->user->role->name : 'Guest',
                    'phone_number' => $r->phone_number ?? '—',
                    'request_type' => $r->request_type,
                    'request_content' => $r->request_content,
                    'status' => $r->status,
                    'sent_time' => Carbon::parse($r->sent_time)->format('d M Y, H:i')
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Update request status
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        // Use find() instead of findOrFail()
        $requestLog = RequestLog::find($id);

        if (!$requestLog) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
                'data' => null
            ], 404);
        }

        $requestLog->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Request status updated successfully'
        ]);
    }

    /**
     * Get all owners
     */
    public function getOwners()
    {
        $owners = User::where('role_id', 2)
            ->get()
            ->map(function($u) {
                return [
                    'user_id' => $u->user_id,
                    'name' => $u->name,
                    'email' => $u->email
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $owners
        ]);
    }

    /**
     * Get farms by owner
     */
    public function getFarmsByOwner($owner_id)
    {
        $farms = Farm::where('owner_id', $owner_id)
            ->get()
            ->map(function($f) {
                return [
                    'farm_id' => $f->farm_id,
                    'farm_name' => $f->farm_name,
                    'location' => $f->location,
                    'peternak_id' => $f->peternak_id
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $farms
        ]);
    }

    /**
     * Get peternaks by owner
     */
    public function getPeternaks($owner_id)
    {
        $peternaks = User::where('role_id', 3)
            ->whereHas('assignedFarm', function($q) use ($owner_id) {
                $q->where('owner_id', $owner_id);
            })
            ->get()
            ->map(function($u) {
                return [
                    'user_id' => $u->user_id,
                    'name' => $u->name,
                    'email' => $u->email
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $peternaks
        ]);
    }

    /**
     * Helper: Get farm config as array (EAV pattern)
     */
    private function getFarmConfigArray($farmId)
    {
        return DB::table('farm_config')
            ->where('farm_id', $farmId)
            ->pluck('value', 'parameter_name')
            ->toArray();
    }

    /**
     * Helper: Create default farm configuration
     */
    private function createDefaultConfig($farmId)
    {
        $defaults = [
            'suhu_normal_min' => 28,
            'suhu_normal_max' => 32,
            'suhu_kritis_rendah' => 25,
            'suhu_kritis_tinggi' => 35,
            'kelembapan_normal_min' => 50,
            'kelembapan_normal_max' => 70,
            'kelembapan_kritis_rendah' => 40,
            'kelembapan_kritis_tinggi' => 80,
            'amonia_max' => 20,
            'amonia_kritis' => 30
        ];

        foreach ($defaults as $key => $value) {
            FarmConfig::create([
                'farm_id' => $farmId,
                'parameter_name' => $key,
                'value' => $value
            ]);
        }
    }

    /**
     * Get single farm details (for Manajemen Kandang)
     */
    public function getFarmDetails($id)
    {
        $farm = Farm::with(['owner', 'peternak'])->find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'farm_id' => $farm->farm_id,
                'farm_name' => $farm->farm_name,
                'location' => $farm->location,
                'farm_area' => $farm->farm_area,
                'initial_population' => $farm->initial_population,
                'owner_id' => $farm->owner_id,
                'owner_name' => $farm->owner ? $farm->owner->name : null,
                'peternak_id' => $farm->peternak_id,
                'peternak_name' => $farm->peternak ? $farm->peternak->name : null
            ]
        ]);
    }

    /**
     * Assign peternak to farm (for Manajemen Kandang)
     */
    public function assignPeternak(Request $request, $id)
    {
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'peternak_id' => 'required|exists:users,user_id'
        ]);

        $peternak = User::find($validated['peternak_id']);

        // Validation 1: Peternak must be role_id = 3
        if ($peternak->role_id != 3) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a peternak'
            ], 422);
        }

        // Validation 2: Check if peternak already assigned to another farm
        $existingAssignment = Farm::where('peternak_id', $validated['peternak_id'])
            ->where('farm_id', '!=', $id)
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Peternak sudah ditugaskan di kandang lain: ' . $existingAssignment->farm_name
            ], 422);
        }

        // Validation 3: Peternak must belong to farm's owner
        // Get all peternaks that belong to this farm's owner
        $ownerPeternaks = User::where('role_id', 3)
            ->whereHas('assignedFarm', function($q) use ($farm) {
                $q->where('owner_id', $farm->owner_id);
            })
            ->orWhereDoesntHave('assignedFarm')
            ->pluck('user_id')
            ->toArray();

        // For now, we'll allow any peternak without assignment
        // You can enforce stricter validation if needed

        // Assign peternak to farm
        $farm->peternak_id = $validated['peternak_id'];
        $farm->save();

        return response()->json([
            'success' => true,
            'message' => 'Peternak assigned successfully',
            'data' => [
                'farm_id' => $farm->farm_id,
                'peternak_id' => $farm->peternak_id,
                'peternak_name' => $peternak->name
            ]
        ]);
    }

    /**
     * Update farm area (for Manajemen Kandang)
     */
    public function updateFarmArea(Request $request, $id)
    {
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'farm_area' => 'required|numeric|min:1'
        ]);

        $farm->farm_area = $validated['farm_area'];
        $farm->save();

        return response()->json([
            'success' => true,
            'message' => 'Farm area updated successfully',
            'data' => [
                'farm_id' => $farm->farm_id,
                'farm_area' => $farm->farm_area
            ]
        ]);
    }

    /**
     * Update farm population (for Manajemen Kandang)
     */
    public function updateFarmPopulation(Request $request, $id)
    {
        $farm = Farm::find($id);

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'initial_population' => 'required|integer|min:1'
        ]);

        $farm->initial_population = $validated['initial_population'];
        $farm->save();

        return response()->json([
            'success' => true,
            'message' => 'Farm population updated successfully',
            'data' => [
                'farm_id' => $farm->farm_id,
                'initial_population' => $farm->initial_population
            ]
        ]);
    }
}
