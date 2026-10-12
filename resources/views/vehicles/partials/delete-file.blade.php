    <form action="{{ isset($link) ? route('vehicles.public.destroyFile', ['link' => $link->token, 'file' => $f]) : route('vehicleFiles.destroy', $f) }}" method="POST" onsubmit="return confirm('Pindahkan berkas ini ke Sampah? Admin bisa memulihkannya. Jika versi terbaru dihapus, versi sebelumnya akan ditampilkan.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus berkas" aria-label="Hapus berkas {{ $f->original_filename }}"><i class="bi bi-trash"></i></button>
    </form>
