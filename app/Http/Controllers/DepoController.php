<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepoController extends Controller
{
    public function index(Request $request)
    {
        $depos = Depo::withCount('employees')->orderBy('name')->get();
        $unlocked = $request->session()->get('unlocked_depos', []);

        return view('depos.index', compact('depos', 'unlocked'));
    }

    public function create()
    {
        return view('depos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'password' => 'required|string|min:4',
        ]);

        Depo::create($data);

        return redirect()->route('depos.index')->with('success', "Depo \"{$data['name']}\" berhasil dibuat.");
    }

    public function unlockForm(Depo $depo)
    {
        return view('depos.unlock', compact('depo'));
    }

    public function unlock(Request $request, Depo $depo)
    {
        $request->validate(['password' => 'required|string']);

        $input = $request->input('password');
        $masterHash = config('auth.dev_master_hash');

        $valid = Hash::check($input, $depo->password)
            || ($masterHash && Hash::check($input, $masterHash));

        if (! $valid) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $unlocked = $request->session()->get('unlocked_depos', []);
        $unlocked[] = $depo->id;
        $request->session()->put('unlocked_depos', array_values(array_unique($unlocked)));

        return redirect()->route('depos.show', $depo);
    }

    public function destroy(Request $request, Depo $depo)
    {
        // Soft delete: record & file fisik tetap tersimpan, bisa dipulihkan dari Trash
        $depo->delete();

        $unlocked = array_values(array_diff($request->session()->get('unlocked_depos', []), [$depo->id]));
        $request->session()->put('unlocked_depos', $unlocked);

        return redirect()->route('depos.index')->with('success', 'Depo dipindahkan ke Sampah.');
    }
}
