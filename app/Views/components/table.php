<?php
/**
 * Component Table
 * 
 * Tableau réutilisable pour afficher des données.
 * 
 * Paramètres :
 * - columns (array) : Configuration des colonnes
 *   [
 *     ['label' => 'Nom', 'key' => 'name', 'sortable' => true],
 *     ['label' => 'Email', 'key' => 'email'],
 *   ]
 * - rows (array) : Données à afficher
 * - actions (array|null) : Actions disponibles par ligne
 *   [
 *     ['label' => 'Modifier', 'icon' => 'fa-edit', 'url' => '/edit/{id}', 'color' => 'primary'],
 *     ['label' => 'Supprimer', 'icon' => 'fa-trash', 'url' => '/delete/{id}', 'color' => 'red', 'confirm' => true],
 *   ]
 * - emptyMessage (string) : Message si aucune donnée
 * - striped (bool) : Lignes alternées
 * - hoverable (bool) : Effet hover sur les lignes
 * 
 * @package STM
 * @version 2.0
 */

// Valeurs par défaut
$columns = $columns ?? [];
$rows = $rows ?? [];
$actions = $actions ?? null;
$emptyMessage = $emptyMessage ?? 'Aucune donnée disponible';
$striped = $striped ?? true;
$hoverable = $hoverable ?? true;

// Classes conditionnelles
$tableClasses = 'min-w-full divide-y divide-gray-200';
$rowClasses = $hoverable ? 'hover:bg-gray-50 transition-colors' : '';
?>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    
    <!-- Table container avec scroll horizontal -->
    <div class="overflow-x-auto">
        <table class="<?= $tableClasses ?>">
            
            <!-- Header -->
            <thead class="bg-gray-50">
                <tr>
                    <?php foreach ($columns as $column): ?>
                    <th scope="col" 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider <?= ($column['sortable'] ?? false) ? 'cursor-pointer hover:text-primary-600' : '' ?>">
                        <div class="flex items-center gap-2">
                            <span><?= htmlspecialchars($column['label']) ?></span>
                            <?php if ($column['sortable'] ?? false): ?>
                            <i class="fas fa-sort text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                    </th>
                    <?php endforeach; ?>
                    
                    <?php if ($actions): ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            
            <!-- Body -->
            <tbody class="bg-white divide-y divide-gray-200">
                
                <?php if (empty($rows)): ?>
                    <!-- Message si vide -->
                    <tr>
                        <td colspan="<?= count($columns) + ($actions ? 1 : 0) ?>" 
                            class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p class="text-sm font-medium"><?= htmlspecialchars($emptyMessage) ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    
                    <?php foreach ($rows as $index => $row): ?>
                    <tr class="<?= $rowClasses ?> <?= $striped && $index % 2 !== 0 ? 'bg-gray-50' : '' ?>">
                        
                        <?php foreach ($columns as $column): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php
                            $key = $column['key'];
                            $value = $row[$key] ?? '';
                            
                            // Formatter personnalisé si défini
                            if (isset($column['formatter']) && is_callable($column['formatter'])) {
                                echo $column['formatter']($value, $row);
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                        
                        <?php if ($actions): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <?php foreach ($actions as $action): ?>
                                    <?php
                                    // Remplacer {id} dans l'URL
                                    $actionUrl = str_replace('{id}', $row['id'] ?? '', $action['url'] ?? '#');
                                    $actionColor = $action['color'] ?? 'primary';
                                    $actionConfirm = $action['confirm'] ?? false;
                                    
                                    $colorClasses = [
                                        'primary' => 'text-primary-600 hover:text-primary-900',
                                        'red' => 'text-red-600 hover:text-red-900',
                                        'green' => 'text-green-600 hover:text-green-900',
                                        'blue' => 'text-blue-600 hover:text-blue-900',
                                    ];
                                    ?>
                                    
                                    <a href="<?= htmlspecialchars($actionUrl) ?>"
                                       class="<?= $colorClasses[$actionColor] ?? $colorClasses['primary'] ?> transition-colors"
                                       <?= $actionConfirm ? 'data-confirm="Êtes-vous sûr ?"' : '' ?>
                                       title="<?= htmlspecialchars($action['label']) ?>">
                                        <i class="fas <?= $action['icon'] ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                        
                    </tr>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
            </tbody>
            
        </table>
    </div>
    
</div>

<!-- Script pour le tri (basique) -->
<script>
    document.querySelectorAll('th[class*="cursor-pointer"]').forEach(th => {
        th.addEventListener('click', () => {
            console.log('Tri sur:', th.textContent.trim());
            // TODO: Implémenter le tri
        });
    });
</script>
