<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trendy Foods - Espace Réservé</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    
    <!-- Container principal centré -->
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full">
            
            <!-- Carte principale -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-12 border border-white/20">
                
                <!-- Logo -->
                <div class="text-center mb-8">
                    <img src="/stm/public/assets/images/logo.png" 
                         alt="Trendy Foods" 
                         class="h-40 mx-auto mb-4">
                    <p class="text-blue-200 text-sm tracking-widest">PROFESSIONAL ACCESS</p>
                </div>
                
                <!-- Icône lock -->
                <div class="flex justify-center mb-8">
                    <div class="bg-white/10 p-6 rounded-full">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Message -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-white mb-4">
                        Accès Restreint
                    </h2>
                </div>
                
                <!-- Divider -->
                <div class="border-t border-white/20 mb-8"></div>
                
                <!-- Lien site web avec touche rouge -->
                <div class="bg-white/5 rounded-lg p-6 text-center border border-red-500/30">
                    <p class="text-sm text-blue-200 mb-4">
                        <strong class="text-white text-base">Vous êtes perdu ?</strong>
                    </p>
                    <a href="https://www.trendyfoods.com" 
                       target="_blank"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-medium rounded-lg transition-all duration-200 shadow-lg hover:shadow-red-500/50">
                        <span>Visitez notre site web</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
                
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-6 text-blue-200 text-sm">
                © <?php echo date('Y'); ?> Trendy Foods • Belgique • Luxembourg
            </div>
            
        </div>
    </div>
    
</body>
</html>