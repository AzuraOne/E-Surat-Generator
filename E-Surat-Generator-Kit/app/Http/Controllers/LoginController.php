<?php

namespace App\Http\Controllers;

use App\Models\TandaTangan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role == 'pengadaan') {
                return redirect()->route('dashboard.pengadaan');
            }
            if ($user->role == 'manager') {
                return redirect()->route('dashboard.manager');
            }
            if ($user->role == 'vendor') {

                return redirect()->route('dashboard.vendor');
            }
        }

        return back()->withErrors([
            'email' => 'Email yang anda masukkan salah',
            'password' => 'Password yang anda masukkan salah.'
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    public function dashboardpln()
    {
        return view('plninternal.dashboard');
    }
    public function dashboardvendor()
    {
        return view('vendor.dashboard');
    }
    public function usersetting()
    {
        $user = Auth::user();
        $user_profile = User::find($user->id);
        return view('template.usersetting', ['data' => $user_profile]);
    }
    public function gantipass(Request $request)
    {
        // Memvalidasi data yang terkirim
        $request->validate([
            'password_lama' => ['required', function ($attribute, $value, $fail) use ($request) {
                if (!Hash::check($value, $request->user()->password)) {
                    return $fail(__('Password lama tidak cocok'));
                }
            }],
            'password_baru' => ['required', 'confirmed', 'different:password_lama'],
            'password_baru_confirmation' => ['required'],
        ]);
        // Mengambil data dari session lalu mencari di database
        $user = Auth::user();
        $user_profile = User::find($user->id);
        $user_profile->password = bcrypt($request->input('password_baru'));
        $user_profile->save();
        return redirect()->back();
    }
    public function ubahuser(Request $request)
    {
        // Memvalidasi data yang terkirim
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'picture_profile' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Mengambil data dari session lalu mencari di database
        $user = Auth::user();
        $user_profile = User::find($user->id);

        // Cek apakah ada uploadan gambar baru
        if ($request->hasFile('picture_profile')) {
            // Hapus file gambar profil lama jika bukan default.jpg
            if ($user_profile->picture_profile !== 'default.jpg') {
                unlink(public_path('photoprofile/' . $user_profile->picture_profile));
            }

            // Simpan gambar profil yang baru diupload
            $imageName = time() . '.' . $request->picture_profile->extension();
            $request->picture_profile->move(public_path('photoprofile'), $imageName);
            $user_profile->picture_profile = $imageName;
        }

        // Update data nama dan email
        $user_profile->name = $request->input('name');
        $user_profile->email = $request->input('email');
        $user_profile->save();

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function ubahanTandaTangan(Request $request)
    { // Validasi form input jika diperlukan
        $request->validate([
            'signature' => 'required'
        ]);

        // Dapatkan ID akun pengguna yang sedang login
        $userId = auth()->id();

        // Cari tanda tangan yang terkait dengan ID akun
        $tandaTangan = TandaTangan::where('id_akun', $userId)->first();

        if (!$tandaTangan) {
            // Jika tanda tangan belum ada, buat entri baru di tabel tanda_tangans
            $tandaTangan = new TandaTangan();
            $tandaTangan->id_akun = $userId;
        } else {
            // Hapus file tanda tangan lama jika bukan file default
            $oldFilename = $tandaTangan->tandatangan;
            if ($oldFilename !== 'default.png') {
                Storage::disk('public')->delete('tandatangan/' . $oldFilename);
            }
        }

        // Ambil data tanda tangan dari input form
        $signatureData = $request->input('signature');

        // Decode data base64 menjadi gambar
        $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));

        // Buat nama unik untuk file tanda tangan
        $filename = uniqid('signature_') . '.png';

        // Simpan gambar tanda tangan ke storage
        Storage::disk('public')->put('tandatangan/' . $filename, $decodedImage);

        // Update kolom tandatangan dengan nama file yang baru
        $tandaTangan->tandatangan = $filename;
        $tandaTangan->save();

        // Redirect atau kembali ke halaman yang sesuai
        return redirect()->back()->with('success', 'Tanda tangan berhasil diperbarui');
    }

    public function registrasivendor()
    {
        return view('login.register');
    }
    public function simpanvendor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',

        ]);

        $dataakun = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => 'vendor',

        ];

        if ($request->hasFile('picture_profile')) {
            $file = $request->file('picture_profile');
            $filename = $file->getClientOriginalName() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/photoprofile', $filename);
            $dataakun['picture_profile'] = $filename;
        }

        $user = User::create($dataakun);
        $datavendor = $request->validate([
            'penyedia' => 'required',
            'direktur' => 'required',
            'alamat_jalan' => 'required',
            'alamat_kota' => 'required',
            'alamat_provinsi' => 'required',
            'bank' => 'required',
            'nomor_rek' => 'required',
        ]);
        $datavendor['id_akun'] = $user->id;

        Vendor::create($datavendor);
        return redirect()->route('login')->with('success', 'Registrasi vendor berhasil. Silakan login.');
    }
}
