<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle ?? "Administration"; ?> - STM v2</title>



    <!-- Tailwind CSS -->

    <script src="https://cdn.tailwindcss.com"></script>



    <!-- Alpine.js -->

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>



    <!-- Chart.js -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

</head>

<body class="bg-gray-100">



    <!-- Messages Flash -->

    <?php if (isset($_SESSION["flash_success"])): ?>

        <div class="fixed top-4 right-4 z-50 max-w-md">

            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg shadow-lg" role="alert">

                <div class="flex items-center">

                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>

                    </svg>

                    <span><?php
                    echo $_SESSION["flash_success"];
                    unset($_SESSION["flash_success"]);
                    ?></span>

                </div>

            </div>

        </div>

    <?php endif; ?>



    <?php if (isset($_SESSION["flash_error"])): ?>

        <div class="fixed top-4 right-4 z-50 max-w-md">

            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-lg" role="alert">

                <div class="flex items-center">

                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>

                    </svg>

                    <span><?php
                    echo $_SESSION["flash_error"];
                    unset($_SESSION["flash_error"]);
                    ?></span>

                </div>

            </div>

        </div>

    <?php endif; ?>



    <?php if (isset($_SESSION["flash_warning"])): ?>

        <div class="fixed top-4 right-4 z-50 max-w-md">

            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg shadow-lg" role="alert">

                <div class="flex items-center">

                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>

                    </svg>

                    <span><?php
                    echo $_SESSION["flash_warning"];
                    unset($_SESSION["flash_warning"]);
                    ?></span>

                </div>

            </div>

        </div>

    <?php endif; ?>



    <!-- Navigation -->

    <nav class="bg-white shadow-sm border-b border-gray-200">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex justify-between h-16">

                <div class="flex">

                    <!-- Logo -->

                    <div class="flex-shrink-0 flex items-center">

                        <a href="/stm/admin/dashboard" class="text-xl font-bold text-blue-600">

                            STM v2

                        </a>

                    </div>



                    <!-- Navigation principale -->

                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">

                        <a href="/stm/admin/dashboard"

                           class="<?php echo strpos($_SERVER["REQUEST_URI"], "/dashboard") !== false
                               ? "border-blue-500 text-gray-900"
                               : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">

                            📊 Dashboard

                        </a>

                        <a href="/stm/admin/campaigns"

                           class="<?php echo strpos($_SERVER["REQUEST_URI"], "/campaigns") !== false
                               ? "border-blue-500 text-gray-900"
                               : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700"; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">

                            🎯 Campagnes

                        </a>

                    </div>

                </div>



                <!-- User menu -->

                <div class="flex items-center">

                    <span class="text-sm text-gray-700 mr-4">

                        👤 <?php echo $_SESSION["user_email"] ?? "Admin"; ?>

                    </span>

                    <a href="/stm/admin/logout"

                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">

                        Déconnexion

                    </a>

                </div>

            </div>

        </div>

    </nav>



    <!-- Contenu principal -->

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <?php echo $content; ?>

    </main>



    <!-- Footer -->

    <footer class="bg-white border-t border-gray-200 mt-12">

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

            <p class="text-center text-sm text-gray-500">

                © <?php echo date("Y"); ?> Trendy Foods - STM v2

            </p>

        </div>

    </footer>



</body>

</html>