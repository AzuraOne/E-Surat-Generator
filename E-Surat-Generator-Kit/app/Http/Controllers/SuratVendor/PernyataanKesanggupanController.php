<?php

namespace App\Http\Controllers\SuratVendor;

use App\Http\Controllers\Controller;
use App\Models\DokumenVendor\Formpenawaranharga;
use App\Models\DokumenVendor\PernyataanKesanggupan;

use App\Models\JenisDokumenKelengkapan;
use App\Models\KelengkapanDokumenVendor;
use App\Models\KontrakKerja;
use App\Models\PembuatanSuratKontrak;
use App\Models\TandaTangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PernyataanKesanggupanController extends Controller
{


    public function refresh($id)
    {
        // Cek apakah terdapat data penawaran dengan id_kontrakkerja yang sesuai
        // $jenisDokumen = JenisDokumenKelengkapan::where('no_dokumen', "pakta_integritas_")
        // $kelengkapan = KelengkapanDokumenVendor::where('id_kontrakkerja',$id)->with('jenisDokumen');
        $jenisDokumen = JenisDokumenKelengkapan::where('no_dokumen', 'surat_pernyataan_sanggup_menyelesaikan_pekerjaan_dengan_baik_')
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


        // $kelengkapan = KelengkapanDokumenVendor::where('id_kontrakkerja', $id)->where('id_jenis_dokumen', $jenisDokumen->id_jenis)->with('pernyataanKesanggupan')->first();
        $kelengkapan = KelengkapanDokumenVendor::where('id_kontrakkerja', $id)->where('id_jenis_dokumen', $jenisDokumen->id_jenis)->first();

        return $kelengkapan;

        // if ($kelengkapan->pernyataankesanggupan === null) {

        //     $pernyataankesanggupan = new PernyataanKesanggupan();
        //     $pernyataankesanggupan->id_dokumen = $kelengkapan->id_dokumen;

        //     $pernyataankesanggupan->save();
        // }

        // $pernyataankesanggupan1 = PernyataanKesanggupan::where('id_dokumen', $kelengkapan->id_dokumen)->first();
        // return $pernyataankesanggupan1;
    }

    public function create($id)
    {
        $penawaran = $this->refresh($id);

        $data = [
            'id' => $id,
            'nama' => $penawaran->nama,
            'jabatan' => $penawaran->jabatan,
            'nama_perusahaan' =>  $penawaran->nama_perusahaan,
            'atas_nama' =>  $penawaran->atas_nama,
            'alamat_perusahaan' =>  $penawaran->alamat,
            'telepon_fax' =>  $penawaran->telepon_fax,
            'email_perusahaan' =>  $penawaran->email_perusahaan,
            'nama_pekerjaan' => strtoupper(KontrakKerja::find($id)->nama_kontrak),
            'nomor_rks' =>  "Nomor RKS",
            'tanggal_rks' =>  "Tanggal _RKS",
            'kota_surat' => "Kota Surat",
            'tanggal_surat' =>  "Tanggal Surat",
            'nama_terang' => "Nama Terang",
        ];
        // dd($data);
        return view('vendor.form_penawaran.pernyataansanggup.create', $data);
    }

    public function update(Request $request, $id)
    {
        $penawaran = $this->refresh($id);

        // Validasi input
        $validatedData = $request->validate([
            'nama' => 'required',
            'jabatan' => 'required',
            'bertindak_untuk' => 'required',
            'atas_nama' => 'required',
            'alamat' => 'required',
            'telepon_fax' => 'required',
            'email' => 'required|email',
        ]);

        $pernyataankesanggupan = PernyataanKesanggupan::find($penawaran->id);
        $pernyataankesanggupan->nama = $validatedData['nama'];
        $pernyataankesanggupan->jabatan = $validatedData['jabatan'];
        $pernyataankesanggupan->nama_perusahaan = $validatedData['bertindak_untuk'];
        $pernyataankesanggupan->atas_nama = $validatedData['atas_nama'];
        $pernyataankesanggupan->alamat = $validatedData['alamat'];
        $pernyataankesanggupan->telepon_fax = $validatedData['telepon_fax'];
        $pernyataankesanggupan->email_perusahaan = $validatedData['email'];

        $pernyataankesanggupan->save();

        return redirect()->route('vendor.kontrakkerja.detail', $id);
    }

    public function halamanttd($id)
    {
        return view('vendor.form_penawaran.pernyataansanggup.halamanttd', compact('id'));
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
        $formpenawaran = Formpenawaranharga::with(['dokumen' => function ($query) use ($id) {
            $query->where('id_kontrakkerja', $id);
        }])->first();
        $kopsurat = asset('/storage/' . $formpenawaran->kopsuratpath . '/' . $formpenawaran->kopsurat);


        // Informasi RKS
        $pembuatansuratkontrak = PembuatanSuratKontrak::where('id_kontrakkerja', $id)->where('nama_surat', 'nomor_rks')->first();

        // Informasi Kontrakkerja
        $kontrakkerja = KontrakKerja::find($id)->with('vendor')->first();
        $data = [
            'kopsurat' => $kopsurat,
            'nama' => $kontrakkerja->vendor->direktur,
            'jabatan' => 'Direktur',
            'nama_perusahaan' =>  $kontrakkerja->vendor->penyedia,
            // 'atas_nama' =>  $kontrakkerja->vendor->penyedia,
            'alamat_perusahaan' =>  $kontrakkerja->vendor->alamat_jalan . ', ' . $kontrakkerja->vendor->alamat_kota . ', ' . $kontrakkerja->vendor->alamat_provinsi,
            'telepon_fax' =>  $kontrakkerja->vendor->telepon_fax,
            'email_perusahaan' =>  $kontrakkerja->vendor->email_perusahaan,
            'nama_pekerjaan' => strtoupper(KontrakKerja::find($id)->nama_kontrak),
            'nomor_rks' => $pembuatansuratkontrak->no_surat,
            'tanggal_rks' => Carbon::parse($pembuatansuratkontrak->tanggal_pembuatan)->locale('id')->isoFormat('D MMMM YYYY'),
            'nama_perusahaan_terang' => isset($kontrakkerja->vendor->penyedia) ? $kontrakkerja->vendor->penyedia  : 'PT./CV ........ ',
            'kota_surat' => $formpenawaran->nama_kota,
            'tanggal_surat' => Carbon::parse($formpenawaran->tanggal_pembuatan_surat)->locale('id')->isoFormat('D MMMM YYYY'),
        ];

        // Generate the PDF using laravel-dompdf

        $pdf = PDF::loadView('vendor.form_penawaran.pernyataansanggup.pdf', $data);

        // Output the generated PDF to the browser
        return $pdf->stream('pernyataan_sanggup.pdf');
    }
}
