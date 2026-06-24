{{-- resources/views/security-audit/index.blade.php --}}
    <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Security Audit Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 text-gray-900">
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Security Audit Reports</h1>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
            <tr>
                <th class="text-left p-3">ID</th>
                <th class="text-left p-3">Audited At</th>
                <th class="text-left p-3">Host</th>
                <th class="text-left p-3">PHP</th>
                <th class="text-left p-3">Risk Score</th>
                <th class="text-left p-3">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
                <tr class="border-t">
                    <td class="p-3">{{ $report->id }}</td>
                    <td class="p-3">{{ $report->audited_at?->format('Y-m-d H:i:s') }}</td>
                    <td class="p-3">{{ $report->hostname }}</td>
                    <td class="p-3">{{ $report->php_version }}</td>
                    <td class="p-3">
                        <span class="inline-flex rounded-full px-3 py-1 text-white {{ $report->risk_score > 0 ? 'bg-red-600' : 'bg-green-600' }}">
                            {{ $report->risk_score }}
                        </span>
                    </td>
                    <td class="p-3">
                        <a class="text-blue-600 hover:underline" href="{{ route('security-audit.show', $report) }}">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="p-4" colspan="6">No reports yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $reports->links() }}
    </div>
</div>
</body>
</html>
