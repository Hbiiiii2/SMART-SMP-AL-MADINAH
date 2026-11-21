<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14px; text-align: left;">
                @if(\Carbon\Carbon::parse($startDate)->format('Y-m') === \Carbon\Carbon::parse($endDate)->format('Y-m'))
                    PERIODE - {{ strtoupper(\Carbon\Carbon::parse($startDate)->translatedFormat('F Y')) }}
                @else
                    PERIODE - {{ strtoupper(\Carbon\Carbon::parse($startDate)->translatedFormat('d M Y')) }} s/d {{ strtoupper(\Carbon\Carbon::parse($endDate)->translatedFormat('d M Y')) }}
                @endif
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: left;">NAMA</th>
            <th colspan="3" style="text-align: center;">{{ strtoupper($siswa->nama_lengkap) }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: left;">NIS</th>
            <th colspan="3" style="text-align: center;">{{ $siswa->nisn }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: left;">KELAS</th>
            <th colspan="3" style="text-align: center;">{{ $siswa->kelas }}</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th style="border: 1px solid #000000; text-align: center;">no</th>
            <th style="border: 1px solid #000000; text-align: center;">tanggal kehadiran</th>
            <th style="border: 1px solid #000000; text-align: center;">status</th>
            <th style="border: 1px solid #000000; text-align: center;">jam masuk</th>
            <th style="border: 1px solid #000000; text-align: center;">jam keluar</th>
            <th style="border: 1px solid #000000; text-align: center;">lokasi absensi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
        <tr>
            <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
            <td style="border: 1px solid #000000; text-align: left;">{{ strtolower($row->status) }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ $row->jam_masuk ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i:s') : '-' }}</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ $row->jam_keluar ? \Carbon\Carbon::parse($row->jam_keluar)->format('H:i:s') : '-' }}</td>
            <td style="border: 1px solid #000000; text-align: left;">{{ $row->lokasi_absensi ?? 'rfid' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
