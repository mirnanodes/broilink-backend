<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IotData;
use Carbon\Carbon;

class SimulationController extends Controller
{
    public function storeManualIot(Request $request)
    {
        // 1. Validasi Input (Pastikan angka)
        $validated = $request->validate([
            'farm_id' => 'required',
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric',
            'ammonia' => 'required|numeric',
        ]);

        try {
            // 2. Masukkan ke Database
            $data = IotData::create([
                'farm_id' => $validated['farm_id'],
                'temperature' => $validated['temperature'],
                'humidity' => $validated['humidity'],
                'ammonia' => $validated['ammonia'],

                // --- BAGIAN INI SUDAH DIPERBAIKI ---
                // Kita pakai 'manual' (pendek) supaya muat di kolom database
                'data_source' => 'manual',
                // -----------------------------------

                'timestamp' => Carbon::now(),
            ]);

            // 3. Respon Sukses
            return response()->json([
                'status' => 'success',
                'message' => '✅ Data Simulasi Berhasil Masuk!',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            // 4. Respon Gagal (Jaga-jaga)
            return response()->json([
                'status' => 'error',
                'message' => '❌ Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
