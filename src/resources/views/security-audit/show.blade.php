{{-- resources/views/security-audit/show.blade.php --}}
    <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Security Audit Report #{{ $report->id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-900">
<div class="max-w-7xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Security Audit Report #{{ $report->id }}</h1>
        <a href="{{ route('security-audit.index') }}" class="text-blue-600 hover:underline">Back</a>
    </div>

    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-sm text-gray-500">Host</div>
            <div class="font-semibold">{{ $report->hostname }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-sm text-gray-500">PHP</div>
            <div class="font-semibold">{{ $report->php_version }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-sm text-gray-500">Branch</div>
            <div class="font-semibold">{{ $report->php_branch }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow p-4">
            <div class="text-sm text-gray-500">Risk score</div>
            <div class="font-semibold {{ $report->risk_score > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $report->risk_score }}
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Summary checks</h2>
        <div class="grid md:grid-cols-2 gap-3">
            @foreach(($report->summary_checks ?? []) as $label => $value)
                <div class="border rounded-xl p-3 flex items-center justify-between">
                    <span>{{ $label }}</span>
                    <span class="px-3 py-1 rounded-full text-white {{ $value ? 'bg-green-600' : 'bg-red-600' }}">
                        {{ $value ? 'OK' : 'RISK' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4">PHP version profile</h2>
            <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->version_profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Headers</h2>
            <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Dangerous functions</h2>
        <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->dangerous_functions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Paths</h2>
        <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Outside document root</h2>
        <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->outside_docroot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Sensitive files</h2>
        <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->sensitive_files, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Upload risks</h2>
        <pre class="text-xs overflow-auto bg-gray-50 p-4 rounded-xl">{{ json_encode($report->upload_risks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
</body>
</html>
