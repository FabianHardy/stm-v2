<!DOCTYPE html>
<html lang="<?= $lang ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?= $title ?? 'STM v2 - Trendy Foods' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- ============================================ -->
    <!-- FRONTEND LIBRARIES VIA CDN (Pas besoin de npm !) -->
    <!-- ============================================ -->
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configuration Tailwind personnalisée
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>
    
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
    </style>
    
    <?php if (isset($additional_head)): ?>
        <?= $additional_head ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Header -->
    <?php require_once __DIR__ . '/components/header.php'; ?>
    
    <!-- Messages Flash -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container mx-auto px-4 py-4">
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert alert-<?= $type ?> p-4 rounded-lg mb-4 
                    <?= $type === 'success' ? 'bg-green-100 text-green-800' : '' ?>
                    <?= $type === 'error' ? 'bg-red-100 text-red-800' : '' ?>
                    <?= $type === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                    <?= $type === 'info' ? 'bg-blue-100 text-blue-800' : '' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <?php require_once __DIR__ . '/components/footer.php'; ?>
    
    <!-- Scripts additionnels -->
    <?php if (isset($additional_scripts)): ?>
        <?= $additional_scripts ?>
    <?php endif; ?>
    
</body>
</html>
