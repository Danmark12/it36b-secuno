<?php
// PHP logic to fetch dashboard data (example comments)
// You'll need to include your database configuration here if dashboard.php
// is accessed directly or needs database access for its own logic,
// but if it's always included by index.php, index.php's require is enough.
// require_once '../db/config.php';

// Example data from a database (replace with actual queries)
// For demonstration, these are static.
$total_reports = 15;
$pending_reports = 3;
$resolved_reports = 12;

$recent_reports = [
    ['id' => 'RPT-001', 'type' => 'Suspicious Activity', 'status' => 'In Progress', 'date' => '2024-05-21'],
    ['id' => 'RPT-002', 'type' => 'Account Issue', 'status' => 'Resolved', 'date' => '2024-05-15'],
    ['id' => 'RPT-003', 'type' => 'Feature Request', 'status' => 'New', 'date' => '2024-05-10'],
    ['id' => 'RPT-004', 'type' => 'Bug Report', 'status' => 'In Progress', 'date' => '2024-05-08'],
    ['id' => 'RPT-005', 'type' => 'General Inquiry', 'status' => 'Resolved', 'date' => '2024-05-01'],
];
?>

<div class="mx-auto bg-white rounded-lg shadow-xl p-6 md:p-8 w-full max-w-4xl">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-6 text-center">Welcome, User!</h1>
    <p class="text-center text-gray-600 mb-8">Here's a summary of your activities and reports.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card bg-blue-600 text-white p-6 rounded-xl flex flex-col items-center justify-center h-40">
            <i class="fas fa-folder-open text-4xl mb-3"></i>
            <div class="text-lg font-semibold">My Total Reports</div>
            <div class="text-4xl font-bold mt-2"><?php echo $total_reports; ?></div>
        </div>

        <div class="card bg-yellow-500 text-white p-6 rounded-xl flex flex-col items-center justify-center h-40">
            <i class="fas fa-hourglass-half text-4xl mb-3"></i>
            <div class="text-lg font-semibold">Pending Reports</div>
            <div class="text-4xl font-bold mt-2"><?php echo $pending_reports; ?></div>
        </div>

        <div class="card bg-green-600 text-white p-6 rounded-xl flex flex-col items-center justify-center h-40">
            <i class="fas fa-check-double text-4xl mb-3"></i>
            <div class="text-lg font-semibold">Resolved Reports</div>
            <div class="text-4xl font-bold mt-2"><?php echo $resolved_reports; ?></div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
        <a href="report_incident.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-md text-center transition duration-300 ease-in-out h-12 flex items-center justify-center">
            <i class="fas fa-plus-circle mr-2"></i> Report New Incident
        </a>
        <a href="my_reports.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg shadow-md text-center transition duration-300 ease-in-out h-12 flex items-center justify-center">
            <i class="fas fa-list-alt mr-2"></i> View All My Reports
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <h2 class="text-2xl font-bold text-gray-800 p-6 border-b border-gray-200">Your Recent Reports</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-xl">
                            Report ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-xl">
                            Date Submitted
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_reports)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent reports found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_reports as $report): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($report['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($report['type']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php
                                    $status_class = '';
                                    switch ($report['status']) {
                                        case 'In Progress':
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'Resolved':
                                            $status_class = 'bg-green-100 text-green-800';
                                            break;
                                        case 'New':
                                            $status_class = 'bg-blue-100 text-blue-800';
                                            break;
                                        default:
                                            $status_class = 'bg-gray-100 text-gray-800';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($report['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($report['date']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>