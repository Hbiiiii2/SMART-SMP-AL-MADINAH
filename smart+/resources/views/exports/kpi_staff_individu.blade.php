<table>
    <tr>
        <td colspan="6" style="font-weight: bold; font-size: 14px;">PERIODE - {{ $periode }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; font-size: 14px;">NAMA</td>
        <td colspan="5" style="font-weight: bold; font-size: 14px;">{{ $staff->nama_lengkap }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; font-size: 14px;">NIP</td>
        <td colspan="5" style="font-weight: bold; font-size: 14px;">{{ $staff->nip ?? '-' }}</td>
    </tr>
    <tr></tr>
    <tr>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">no</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">tanggal kehadiran</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">status</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">jam masuk</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">jam keluar</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: bold;">lokasi absensi</td>
    </tr>
    @foreach($absensi as $index => $ab)
    <tr>
        <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ \Carbon\Carbon::parse($ab->tanggal)->format('d/m/Y') }}</td>
        <td style="border: 1px solid #000;">{{ strtolower($ab->status) }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $ab->waktu_check_in ? \Carbon\Carbon::parse($ab->waktu_check_in)->format('H:i:s') : '-' }}</td>
        <td style="border: 1px solid #000; text-align: center;">{{ $ab->waktu_check_out ? \Carbon\Carbon::parse($ab->waktu_check_out)->format('H:i:s') : '-' }}</td>
        <td style="border: 1px solid #000;">{{ $ab->latitude && $ab->longitude ? $ab->latitude . ', ' . $ab->longitude : 'rfid' }}</td>
    </tr>
    @endforeach
</table>
