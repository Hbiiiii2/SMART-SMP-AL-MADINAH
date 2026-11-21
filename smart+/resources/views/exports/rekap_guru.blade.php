<table>
    <thead>
        <tr>
            <th colspan="9" style="font-weight: bold; text-align: center; font-size: 14px;">
                REKAP {{ strtoupper(\Carbon\Carbon::parse($startDate)->translatedFormat('j-F-Y')) }} s/d {{ strtoupper(\Carbon\Carbon::parse($endDate)->translatedFormat('j-F-Y')) }}
            </th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">No</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Tanggal</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NIP</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Nama Guru</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Status</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Jam Masuk</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Jam Keluar</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">lokasi</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
        <tr>
            <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->guru->nip ?? '-' }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->guru->nama_lengkap ?? '-' }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->status }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->jam_masuk ? $row->jam_masuk->format('H:i:s') : '-' }}</td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->jam_keluar ? $row->jam_keluar->format('H:i:s') : '-' }}</td>
            <td style="text-align: center; border: 1px solid #000000;"></td>
            <td style="text-align: center; border: 1px solid #000000;">{{ $row->keterangan ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
