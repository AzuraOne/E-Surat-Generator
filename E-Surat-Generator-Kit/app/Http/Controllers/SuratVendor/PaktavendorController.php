<?php

namespace App\Http\Controllers\SuratVendor;

use App\Http\Controllers\Controller;
use App\Models\DokumenVendor\Paktavendor;
use App\Models\FormPenawaran\FormPenawaranHarga;
use App\Models\JenisDokumenKelengkapan;
use App\Models\KelengkapanDokumenVendor;
use App\Models\TandaTangan;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PaktavendorController extends Controller
{
    public function refresh($id)
    {
        // Cek apakah terdapat data penawaran dengan id_kontrakkerja yang sesuai
        // $jenisDokumen = JenisDokumenKelengkapan::where('no_dokumen', "pakta_integritas_")
        // $kelengkapan = KelengkapanDokumenVendor::where('id_kontrakkerja',$id)->with('jenisDokumen');
        $jenisDokumen = JenisDokumenKelengkapan::where('no_dokumen', 'pakta_integritas_')
            ->with(['kelengkapanDokumenVendors' => function ($query) use ($id) {
                $query->where('id_kontrakkerja', $id);
            }])
            ->first();

        if ($jenisDokumen->kelengkapanDokumenVendors->isEmpty()) {


            // Membuat record baru di tabel kelengkapan_dokumen_vendors
            KelengkapanDokumenVendor::create([
                'id_jenis_dokumen' => $jenisDokumen->id_jenis,
                'id_vendor' => Auth::user()->vendor_id,
                'id_kontrakkerja' => $id,
            ])->save();
        }


        $kelengkapan = KelengkapanDokumenVendor::where('id_kontrakkerja', $id)->where('id_jenis_dokumen', $jenisDokumen->id_jenis)->with('paktavendor')->first();



        if ($kelengkapan->paktavendor === null) {

            $paktavendor = new Paktavendor;
            $paktavendor->id_dokumen = $kelengkapan->id_dokumen;

            $paktavendor->save();
        }

        $paktavendor1 = Paktavendor::where('id_dokumen', $kelengkapan->id_dokumen)->first();
        return $paktavendor1;
    }
    public function create($id)
    {
        $penawaran = $this->refresh($id);

        // dd($penawaran);
        $data = [
            'id' => $id,
            'kopsurat' => 'path/to/kopsurat.jpg',
            'nama_pekerjaan' => "Pekerjaan",
            'nomor' => '1234',
            'lampiran' => 'Lorem Ipsum',
            'tanggal' => '2023-05-26',
            'namaPerusahaan' => 'Example Company',
            'alamatPerusahaan' => '123 Example Street, City',
            'pekerjaan' => $penawaran->pekerjaan,
            'tahun_anggaran' => $penawaran->tahun_anggaran,
            'nama' =>  $penawaran->nama,
            'jabatan' => $penawaran->jabatan,
            'nama_perusahaan' => $penawaran->nama_perusahaan,
            'atas_nama' => $penawaran->atas_nama,
            'alamat' => $penawaran->alamat,
            'telepon_fax' => $penawaran->telepon_fax,
            'email_perusahaan' => $penawaran->email_perusahaan,
            'kota' => 'City',
            'tanggal_surat' => '2023-05-26',
            'namaterang' => 'Example Terang',
        ];
        return view('vendor.form_penawaran.paktavendor.create', $data);
    }

    public function update(Request $request, $id)
    {
        $penawaran = $this->refresh($id);
        // Validasi data yang diterima dari form
        $validatedData = $request->validate([
            'pekerjaan' => 'required',
            'tahun_anggaran' => 'required',
            'nama' => 'required',
            'jabatan' => 'required',
            'bertindak_untuk' => 'required',
            'atas_nama' => 'required',
            'alamat' => 'required',
            'telepon_fax' => 'required',
            'email' => 'required|email',
        ]);

        $paktavendor = Paktavendor::find($penawaran->id);
        $paktavendor->pekerjaan = $validatedData['pekerjaan'];
        $paktavendor->tahun_anggaran = $validatedData['tahun_anggaran'];
        $paktavendor->nama = $validatedData['nama'];
        $paktavendor->jabatan = $validatedData['jabatan'];
        $paktavendor->nama_perusahaan = $validatedData['bertindak_untuk'];
        $paktavendor->atas_nama = $validatedData['atas_nama'];
        $paktavendor->alamat = $validatedData['alamat'];
        $paktavendor->telepon_fax = $validatedData['telepon_fax'];
        $paktavendor->email_perusahaan = $validatedData['email'];

        $paktavendor->save();

        return redirect()->route('vendor.kontrakkerja.detail', $id);
    }

    public function halamanttd($id)
    {

        return view('vendor.form_penawaran.paktavendor.halamanttd', compact('id'));
    }
    public function simpanttd(Request $request, $id)
    {
        $formPenawaranHarga = $this->refresh($request->input('id'));

        // Menyimpan file tanda tangan ke storage
        $file = $request->file('file_tandatangan');

        // Generate the new filename using time(), original name, and extension
        $filename = time() . '_' . $file->getClientOriginalName();

        // Store the file with the new filename
        $filePath = $file->storeAs('public/dokumenvendor', $filename);
        $id_kontrakkerja = $request->input('id');


        // Update kolom file_tandatangan, no_unik_ttd, dan tanggal_tandatangan
        $kelengkapandokumen = KelengkapanDokumenVendor::find($formPenawaranHarga->id_dokumen);
        $kelengkapandokumen->file_upload = $filename;
        $kelengkapandokumen->tandatangan = TandaTangan::where('id', Auth::user()->id)->first()->kode_unik;
        $kelengkapandokumen->save();

        return redirect()->route('vendor.kontrakkerja.detail', ['id' => $id_kontrakkerja]);
    }

    public function pdf($id)
    {
        $penawaran = $this->refresh($id);
   
        $data = [
            'kopsurat' => 'path/to/kopsurat.jpg',
            'nama_pekerjaan' => "manaf",
            'nomor' => '1234',
            'lampiran' => 'Lorem Ipsum',
            'tanggal' => '2023-05-26',
            'namaPerusahaan' => 'Example Company',
            'alamatPerusahaan' => '123 Example Street, City',
            'pekerjaan' => 'Example Job',
            'tahun_anggaran' => '2023',
            'nama' =>  $penawaran->nama,
            'jabatan' => $penawaran->jabatan,
            'nama_perusahaan' => $penawaran->bertindak_untuk,
            'atas_nama' => $penawaran->atas_nama,
            'alamat' => $penawaran->alamat,
            'telepon_fax' => $penawaran->telepon_fax,
            'email_perusahaan' => $penawaran->email,
            'kota' => 'City',
            'tanggal_surat' => '2023-05-26',
            'namaterang' => 'Example Terang',
        ];
        // Generate the PDF using laravel-dompdf
        $pdf = PDF::loadView('vendor.form_penawaran.paktavendor.pdf', $data);

        // Set paper size and orientation
        $pdf->setPaper('letter', 'portrait');
        // Output the generated PDF to the browser
        return $pdf->stream('paktavendor.pdf');
    }
}
