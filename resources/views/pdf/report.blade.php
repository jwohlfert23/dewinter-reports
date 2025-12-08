<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Report - {{ $positionTitle }}</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        /* Title Page Styles - Letter size is 8.5x11 inches = 612x792 points */
        .title-page {
            width: 8.5in;
            height: 11in;
            position: relative;
            overflow: hidden;
            page-break-after: always;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 65%;
            object-fit: cover;
            z-index: 1;
        }

        .teal-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 35%;
            background-color: #006D6F;
            z-index: 2;
        }

        .title-card-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
        }

        .title-card-cell {
            position: absolute;
            top: 34%;
            left: 50%;
            margin-left: -250px;
        }

        .title-card {
            display: inline-block;
            width: 500px;
            text-align: center;
            overflow: hidden;
        }

        .title-header {
            background-color: #E08E45;
            color: white;
            padding: 45px 40px;
        }

        .title-header h1 {
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .title-header h2 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .title-body {
            background-color: white;
            padding: 45px 40px 50px;
        }

        .title-body h3 {
            font-size: 26px;
            color: #006D6F;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .client-logo {
            max-width: 180px;
            max-height: 70px;
            margin: 0 auto;
            display: block;
        }

        .client-name-text {
            font-size: 20px;
            color: #006D6F;
            font-weight: bold;
        }

        /* Report Content Styles */
        .report-content {
            padding: 40px 50px;
        }

        .report-title {
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 35px;
            color: #333;
        }

        .status-section {
            margin-bottom: 28px;
            page-break-inside: avoid;
        }

        .status-header {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th {
            background-color: #f0f0f0;
            border: 1px solid #333;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }

        td {
            border: 1px solid #333;
            padding: 8px 12px;
            font-size: 12px;
        }

        tr:nth-child(even) td {
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    {{-- Title Page --}}
    <div class="title-page">
        <img src="{{ asset('img/sf.jpg') }}" class="background-image" alt="San Francisco">
        <div class="teal-bar"></div>
        <div class="title-card-wrapper">
            <div class="title-card-cell">
                <div class="title-card">
                    <div class="title-header">
                        <h1>WEEKLY REPORT</h1>
                        <h2>FOR {{ strtoupper(date('m/d/y', strtotime($date))) }}</h2>
                    </div>
                    <div class="title-body">
                        <h3>{{ $positionTitle }}</h3>
                        @if($clientLogo)
                            <img src="{{ $clientLogo }}" alt="{{ $clientName }}" class="client-logo">
                        @else
                            <p class="client-name-text">{{ $clientName }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Report Content --}}
    <div class="report-content">
        <h1 class="report-title">Weekly Candidate Report</h1>

        @foreach($groupedCandidates as $status => $candidates)
            @if(count($candidates) > 0)
                <div class="status-section">
                    <h2 class="status-header">{{ $status === 'Screening' ? 'DeWinter Screening' : $status }}</h2>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50%;">Candidate Name</th>
                                <th style="width: 50%;">Company</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $candidate)
                                <tr>
                                    <td>{{ $candidate['Candidate'] ?? '' }}</td>
                                    <td>{{ $candidate['Company'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
