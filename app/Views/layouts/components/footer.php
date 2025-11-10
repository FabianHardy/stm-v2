<!-- 
    Composant : Footer Client
    Description : Pied de page avec liens et informations légales
-->

<footer class="bg-gray-800 text-gray-300 mt-16">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- Colonne 1 : À propos -->
            <div>
                <h3 class="text-white font-bold text-lg mb-4">
                    <?= $lang === 'nl' ? 'Over ons' : 'À propos' ?>
                </h3>
                <p class="text-sm leading-relaxed">
                    <?= $lang === 'nl' 
                        ? 'Trendy Foods - Uw groothandelspartner voor kwaliteitsproducten in België en Luxemburg.'
                        : 'Trendy Foods - Votre partenaire grossiste pour des produits de qualité en Belgique et au Luxembourg.' 
                    ?>
                </p>
            </div>
            
            <!-- Colonne 2 : Liens rapides -->
            <div>
                <h3 class="text-white font-bold text-lg mb-4">
                    <?= $lang === 'nl' ? 'Snelle links' : 'Liens rapides' ?>
                </h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="/" class="hover:text-white transition">
                            <?= $lang === 'nl' ? 'Home' : 'Accueil' ?>
                        </a>
                    </li>
                    <li>
                        <a href="/terms" class="hover:text-white transition">
                            <?= $lang === 'nl' ? 'Algemene voorwaarden' : 'Conditions générales' ?>
                        </a>
                    </li>
                    <li>
                        <a href="/privacy" class="hover:text-white transition">
                            <?= $lang === 'nl' ? 'Privacybeleid' : 'Politique de confidentialité' ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Colonne 3 : Contact -->
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Contact</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start space-x-2">
                        <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>info@trendyfoods.be</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>+32 (0)4 XXX XX XX</span>
                    </li>
                </ul>
            </div>
            
        </div>
        
        <!-- Copyright -->
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm">
            <p>&copy; <?= date('Y') ?> Trendy Foods. 
                <?= $lang === 'nl' ? 'Alle rechten voorbehouden.' : 'Tous droits réservés.' ?>
            </p>
        </div>
    </div>
</footer>
