<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Report - {{ $positionTitle }}</title>
    <style>
      @font-face {
        font-family: 'Nunito';
        src: url(data:font/truetype;charset=utf-8;base64,{{ base64_encode(file_get_contents(resource_path('fonts/Nunito-Regular.ttf'))) }}) format('truetype');
        font-weight: 400;
        font-style: normal;
      }

      @font-face {
        font-family: 'Nunito';
        src: url(data:font/truetype;charset=utf-8;base64,{{ base64_encode(file_get_contents(resource_path('fonts/Nunito-Bold.ttf'))) }}) format('truetype');
        font-weight: 700;
        font-style: normal;
      }

      @font-face {
        font-family: 'Nunito';
        src: url(data:font/truetype;charset=utf-8;base64,{{ base64_encode(file_get_contents(resource_path('fonts/Nunito-ExtraBold.ttf'))) }}) format('truetype');
        font-weight: 800;
        font-style: normal;
      }

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
        background-color: #06616f;
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
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 38px;
        margin-bottom: 5px;
        letter-spacing: 1px;
      }

      .title-header h2 {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 34px;
        letter-spacing: 1px;
      }

      .title-body {
        background-color: white;
        padding: 45px 40px 50px;
      }

      .title-body h3 {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 26px;
        color: #006D6F;
        margin-bottom: 30px;
      }

      .client-logo {
        max-width: 180px;
        max-height: 70px;
        margin: 0 auto;
        display: block;
        object-fit: contain;
      }

      .client-name-text {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 20px;
        color: #006D6F;
      }

      /* Report Content Styles */
      .report-content {
        padding: 40px 50px;
      }

      .report-title {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 26px;
        text-align: center;
        margin-bottom: 8px;
        color: #333;
      }

      .status-section {
        padding-top: 36px;
        page-break-inside: avoid;
      }

      .status-header {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        font-size: 15px;
        margin-bottom: 8px;
        color: #333;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
      }

      th {
        font-family: Nunito, Arial, Helvetica, sans-serif;
        background-color: #f0f0f0;
        border: 1px solid #333;
        padding: 8px 12px;
        text-align: left;
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

      .candidate-url {
        text-decoration: none;
        color: #333;
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
                        <th style="width: 50%;">Candidate</th>
                        <th style="width: 50%;">Company</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($candidates as $candidate)
                        <tr>
                            <td>
                                <a href="{{$candidate['LinkedIn URL']}}" class="candidate-url">
                                    {{ $candidate['Candidate'] ?? '' }}
                                </a>
                            </td>
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
