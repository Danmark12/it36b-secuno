<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Custom styles for the user dashboard to enhance aesthetics */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .table-header {
            background-color: #f9fafb; /* Lighter background for table header */
        }
        .table-row:hover {
            background-color: #f3f4f6; /* Hover effect for table rows */
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="container mx-auto bg-white rounded-lg shadow-xl p-6 md:p-8 w-full max-w-4xl">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6 text-center">Welcome, User!</h1>
        <p class="text-center text-gray-600 mb-8">Here's a summary of your activities and reports.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card bg-blue-600 text-white p-6 rounded-xl flex flex-col items-center justify-center">
                <i class="fas fa-folder-open text-4xl mb-3"></i>
                <div class="text-lg font-semibold">My Total Reports</div>
                <div class="text-4xl font-bold mt-2">15</div>
            </div>

            <div class="card bg-yellow-500 text-white p-6 rounded-xl flex flex-col items-center justify-center">
                <i class="fas fa-hourglass-half text-4xl mb-3"></i>
                <div class="text-lg font-semibold">Pending Reports</div>
                <div class="text-4xl font-bold mt-2">3</div>
            </div>

            <div class="card bg-green-600 text-white p-6 rounded-xl flex flex-col items-center justify-center">
                <i class="fas fa-check-double text-4xl mb-3"></i>
                <div class="text-lg font-semibold">Resolved Reports</div>
                <div class="text-4xl font-bold mt-2">12</div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
            <a href="report_incident.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-md text-center transition duration-300 ease-in-out">
                <i class="fas fa-plus-circle mr-2"></i> Report New Incident
            </a>
            <a href="my_reports.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg shadow-md text-center transition duration-300 ease-in-out">
                <i class="fas fa-list-alt mr-2"></i> View All My Reports
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <h2 class="text-2xl font-bold text-gray-800 p-6 border-b border-gray-200">Your Recent Reports</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
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
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                RPT-001
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Suspicious Activity
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    In Progress
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                2024-05-21
                            </td>
                        </tr>
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                RPT-002
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Account Issue
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Resolved
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                2024-05-15
                            </td>
                        </tr>
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                RPT-003
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                Feature Request
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    New
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                2024-05-10
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
