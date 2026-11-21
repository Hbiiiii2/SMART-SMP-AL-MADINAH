<table>
    <thead>
        <tr>
            <th colspan="6">PERIODE - {{ $periodeStr }}</th>
        </tr>
        <tr>
            <th colspan="2">NAMA</th>
            <th colspan="4" style="text-align: left;">{{ strtoupper($siswa->nama_lengkap) }}</th>
        </tr>
        <tr>
            <th colspan="2">NIS</th>
            <th colspan="4" style="text-align: left;">{{ $siswa->nisn }}</th>
        </tr>
        <tr>
            <th colspan="2">MATA PELAJARAN</th>
            <th colspan="4" style="text-align: left;">{{ strtoupper($jadwal->mataPelajaran->nama ?? '-') }}</th>
        </tr>
        <tr>
            <th style="border: 1px solid black; text-align: center;">no</th>
            <th style="border: 1px solid black; text-align: center;">tanggal kehadiran</th>
            <th style="border: 1px solid black; text-align: center;">status</th>
            <th style="border: 1px solid black; text-align: center;">jam masuk</th>
            <th style="border: 1px solid black; text-align: center;">jam keluar</th>
            <th style="border: 1px solid black; text-align: center;">lokasi absensi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($absensis as $index => $abs)
            <tr>
                <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ \Carbon\Carbon::parse($abs->tanggal)->format('d/m/Y') }}</td>
                <td style="border: 1px solid black;">{{ strtolower($abs->status) }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ $abs->jam_masuk ? \Carbon\Carbon::parse($abs->jam_masuk)->format('H:i:s') : '-' }}</td>
                <td style="border: 1px solid black; text-align: center;">{{ $abs->jam_keluar ? \Carbon\Carbon::parse($abs->jam_keluar)->format('H:i:s') : '-' }}</td>
                <td style="border: 1px solid black;">
                    @if(strtolower($abs->status) === 'hadir' || strtolower($abs->status) === 'terlambat')
                        @if($abs->latitude && $abs->longitude)
                            latitude (koordinat)
                        @else
                            rfid
                        @endif
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
