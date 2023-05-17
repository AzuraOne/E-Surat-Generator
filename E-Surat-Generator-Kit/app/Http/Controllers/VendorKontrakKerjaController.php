<?php

namespace App\Http\Controllers;

use App\Models\KontrakKerja;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VendorKontrakKerjaController extends Controller
{
    public function index()
    {
        $status = [
           

            'Kontrak dibatalkan',
            'Kontrak disetujui',
            'Tanda Tangan Vendor',
            'Kontrak Kerja Berjalan'
        ];
        $kontrak = KontrakKerja::whereIn('status', $status)->get();
        return view('vendor.pengisiankontrakkerja',compact('kontrak'));   
    }
    public function detail($id)
    {
        $kontrakkerja = KontrakKerja::find($id);

        // Path File
        $path = storage_path('app/public/dokumenpenawaran/' . $kontrakkerja->filemaster);

        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();

        return view('vendor.detailkontrak', compact('kontrakkerja', 'id'));
    }


    public function pengisiankontrakkerja()
    {

        $status = [
            'Dokumen Input Vendor',
        ];
        $kontrak = KontrakKerja::whereIn('status', $status)->get();
        dd($kontrak);
        // return view('vendor.kontrakkerja', compact('kontrak'));
       
    }
}
