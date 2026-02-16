<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Chaînes de langue pour le placement d'accessibilité.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Patrick Thibaudeau
 */

defined('MOODLE_INTERNAL') || die();

// Chaînes du plugin.
$string['pluginname'] = 'Correcteur d\'accessibilité';
$string['plugindescription'] = 'Outil alimenté par l\'IA pour corriger les problèmes d\'accessibilité WCAG AA dans le contenu HTML';

// Chaînes des paramètres d'administration.
$string['howitworks'] = 'Comment ça fonctionne';
$string['howitworks_desc'] = 'Le Correcteur d\'accessibilité s\'intègre directement dans l\'éditeur HTML et fournit un bouton « Corriger l\'accessibilité ». Lorsqu\'on clique dessus, il analyse le contenu pour les problèmes de conformité WCAG AA et utilise le fournisseur d\'IA configuré (généralement OpenAI) pour suggérer des corrections pour :

• Texte alternatif manquant sur les images
• Texte de lien faible ou générique
• Titres H1 manquants
• Problèmes potentiels de contraste de couleur
• Étiquettes de formulaire manquantes

Le contenu corrigé est présenté dans une vue de comparaison pour révision avant d\'être appliqué.';

$string['howitworks_delegation'] = 'Cette action utilise la capacité Générer du texte du fournisseur en interne. Aucune configuration supplémentaire n\'est nécessaire pour cette action - configurez l\'action Générer du texte dans les paramètres de votre fournisseur.';

$string['requirements'] = 'Exigences';
$string['requirements_desc'] = '<strong>Pour utiliser ce plugin de placement, vous avez besoin de :</strong>

1. <strong>Fournisseur d\'IA configuré :</strong> Allez dans Admin > Fonctionnalités IA > Fournisseurs et assurez-vous qu\'au moins un fournisseur (par ex., OpenAI) est configuré avec une clé API valide.

2. <strong>Action Générer du texte activée :</strong> Dans les paramètres du fournisseur, assurez-vous que l\'action « Générer du texte » est activée pour votre fournisseur choisi.

3. <strong>Outils IA activés dans les cours :</strong> Dans les paramètres du cours, assurez-vous que « Activer les outils IA » est défini sur Oui.

4. <strong>Permissions utilisateur :</strong> Les utilisateurs ont besoin de la capacité « aiplacement/a11y:use » pour accéder à la fonctionnalité. Ceci est accordé par défaut aux étudiants et aux enseignants.

<strong>Aucune configuration supplémentaire n\'est nécessaire pour ce plugin de placement lui-même.</strong> Il utilise automatiquement la configuration existante de votre fournisseur d\'IA.';

// Chaînes des fonctionnalités.
$string['fixaccessibility'] = 'Corriger l\'accessibilité';
$string['fixaccessibility_desc'] = 'Analyse et corrige les problèmes d\'accessibilité WCAG AA dans le contenu HTML';

// Paramètres du fournisseur.
$string['aiprovider'] = 'Fournisseur d\'IA';
$string['aiprovider_desc'] = 'Sélectionnez le fournisseur d\'IA à utiliser pour les corrections d\'accessibilité. Le plugin détectera et utilisera automatiquement les fournisseurs disponibles configurés dans votre instance Moodle. Si aucun fournisseur n\'est sélectionné, le premier fournisseur disponible sera utilisé.';
$string['preferred_provider'] = 'Fournisseur préféré';
$string['preferred_provider_desc'] = 'Choisissez le fournisseur d\'IA à préférer. S\'il n\'est pas disponible, le plugin utilisera le premier fournisseur disponible dans cet ordre : Azure, OpenAI, DeepSeek, Ollama.';

// Chaînes d'erreur.
$string['noaccessibilityissues'] = 'Aucun problème d\'accessibilité trouvé. Le contenu respecte les normes WCAG AA.';
$string['invalidhtml'] = 'Contenu HTML invalide fourni';
$string['generatedinvalidhtml'] = 'Erreur : Le HTML généré est invalide';
$string['nopermission'] = 'Vous n\'avez pas la permission d\'utiliser cette fonctionnalité';
$string['ainotenabledincourse'] = 'Les outils IA ne sont pas activés dans ce cours';
$string['invalidissuedata'] = 'Données de problème invalides fournies';
$string['noimagesource'] = 'Aucune source d\'image trouvée dans les données du problème';
$string['cannotaccessimage'] = 'Impossible d\'accéder à l\'image pour le traitement. L\'image peut ne pas être accessible publiquement.';
$string['noaiprovidersconfigured'] = 'Aucun fournisseur d\'IA n\'est configuré. Veuillez configurer au moins un fournisseur d\'IA dans Administration du site > Fonctionnalités IA > Fournisseurs.';
$string['providernrtconfigured'] = 'Le fournisseur d\'IA « {$a} » n\'est pas configuré. Veuillez le configurer dans Administration du site > Fonctionnalités IA > Fournisseurs.';
$string['providernrtfound'] = 'Le fournisseur d\'IA « {$a} » n\'est pas pris en charge par ce plugin.';
$string['providernotproperlyconfigured'] = 'Le fournisseur d\'IA « {$a} » n\'est pas correctement configuré. Veuillez vérifier les paramètres du fournisseur dans Administration du site > Fonctionnalités IA > Fournisseurs.';
$string['azurenotconfigured'] = 'Le fournisseur Azure OpenAI n\'est pas configuré.';
$string['openainotconfigured'] = 'Le fournisseur OpenAI n\'est pas configuré.';
$string['deepseeknotconfigured'] = 'Le fournisseur DeepSeek n\'est pas configuré.';
$string['ollamanotconfigured'] = 'Le fournisseur Ollama n\'est pas configuré.';

// Chaînes des rapports.
$string['a11yreport'] = 'Rapport d\'analyse d\'accessibilité';
$string['issuesfound'] = '{$a} problème(s) d\'accessibilité trouvé(s)';
$string['changesfixed'] = 'Modifications corrigées';
$string['fixedsuccessfully'] = 'Problèmes d\'accessibilité corrigés avec succès';
$string['viewhtml'] = 'Voir le HTML';
$string['viewcode'] = 'Voir le code';
$string['status'] = 'Statut';
$string['original'] = 'Original';
$string['fixed'] = 'Corrigé';
$string['acceptchanges'] = 'Accepter les modifications';
$string['rejectchanges'] = 'Rejeter les modifications';
$string['preview'] = 'Aperçu';
$string['showme'] = 'Montrez-moi où !';
$string['fixissue'] = 'Corriger';
$string['suggestedfix'] = 'Correction suggérée';
$string['gettingsuggestion'] = 'Obtention de la suggestion...';
$string['hidesuggestion'] = 'Masquer la suggestion';
$string['whythisneedsfixing'] = 'Pourquoi cela doit être corrigé :';
$string['suggestedfixlabel'] = 'Correction suggérée (vous pouvez modifier) :';
$string['fixall'] = 'Tout corriger';
$string['fixing'] = 'Correction en cours...';
$string['issuesfixed'] = 'Corrigé ✓';
$string['applychanges'] = 'Appliquer les modifications';
$string['cancel'] = 'Annuler';

// Paramètres de vérification automatique.
$string['autocheckheading'] = 'Paramètres de vérification automatique';
$string['autocheckheading_desc'] = 'Configurer la vérification automatique de l\'accessibilité dans l\'éditeur';
$string['autocheckdebounce'] = 'Délai de vérification automatique (millisecondes)';
$string['autocheckdebounce_desc'] = 'Délai avant de vérifier automatiquement le contenu après que l\'utilisateur a cessé de taper. Définir à 0 pour désactiver la vérification automatique. Par défaut : 2000 (2 secondes). Des valeurs plus élevées réduisent la charge du serveur mais retardent le retour d\'information.';

// Statut du bouton.
$string['accessibilityok'] = 'Accessibilité : Aucun problème trouvé';
$string['accessibilityissues'] = 'Accessibilité : {$a} problème(s) trouvé(s) - Cliquez pour corriger';

// Chaînes des problèmes d'accessibilité des tableaux.
$string['table_missing_caption'] = 'Tableau sans légende';
$string['table_missing_caption_desc'] = 'Les tableaux doivent avoir un élément de légende pour décrire leur objectif et aider les utilisateurs à comprendre le contenu du tableau.';
$string['table_merged_cells'] = 'Le tableau a des cellules fusionnées';
$string['table_merged_cells_desc'] = 'Les tableaux avec des cellules fusionnées (colspan/rowspan) peuvent être déroutants pour les utilisateurs de lecteurs d\'écran. Utilisez plutôt des associations d\'en-têtes appropriées.';
$string['table_missing_headers'] = 'Tableau sans en-têtes appropriés';
$string['table_missing_headers_desc'] = 'Les tableaux doivent utiliser des éléments th (en-tête de tableau) dans la première ligne pour définir les en-têtes de colonne, aidant les utilisateurs de lecteurs d\'écran à comprendre la structure du tableau.';

// Chaînes des problèmes d'accessibilité des titres.
$string['heading_hierarchy_issue'] = 'Hiérarchie de titres incorrecte';
$string['heading_hierarchy_issue_desc'] = 'Les titres doivent suivre une hiérarchie appropriée (h3, h4, h5, h6) sans sauter de niveaux. Le premier titre doit être h3. Ne passez pas directement de h3 à h5.';
$string['heading_too_long'] = 'Le titre dépasse la limite de caractères';
$string['heading_too_long_desc'] = 'Les titres ne doivent pas dépasser 1000 caractères. Les titres très longs peuvent être difficiles à naviguer et à comprendre pour les utilisateurs de lecteurs d\'écran.';

// Chaînes des problèmes d'accessibilité du contenu sans titre.
$string['unheaded_content'] = 'Contenu non organisé sans titres';
$string['unheaded_content_desc'] = 'Le contenu doit être organisé en sections logiques à l\'aide de titres (h3, h4, h5, h6). Plusieurs paragraphes sans titres peuvent être déroutants pour les utilisateurs de lecteurs d\'écran et rendent le contenu plus difficile à naviguer et à comprendre.';

// Chaînes de description des problèmes (utilisées dans analyze_accessibility_issues).
$string['issue_missing_alt_text'] = 'Image sans texte alternatif';
$string['issue_weak_link_text'] = 'Le lien a un texte faible ou manquant';
$string['issue_contrast'] = 'Contraste de couleur insuffisant';
$string['issue_missing_form_label'] = 'Champ de formulaire sans étiquette';
$string['issue_table_missing_caption'] = 'Tableau sans légende';
$string['issue_table_merged_cells_colspan'] = 'Le tableau a des cellules fusionnées (colspan)';
$string['issue_table_merged_cells_rowspan'] = 'Le tableau a des cellules fusionnées (rowspan)';
$string['issue_table_missing_headers'] = 'Tableau sans ligne d\'en-tête appropriée (éléments th)';
$string['issue_heading_too_long'] = 'Le titre contient plus de 1000 caractères (trouvé : {$a})';
$string['issue_heading_hierarchy_broken'] = 'Hiérarchie de titres brisée : saut de <h{$a->from}> à <h{$a->to}>';
$string['issue_heading_must_start_h3'] = 'Le contenu doit commencer par <h3> comme premier titre (h1 et h2 ne sont pas utilisés)';
$string['issue_unheaded_content'] = '{$a} caractères de contenu trouvés sans titre pour l\'organiser. Le contenu doit être regroupé sous des titres appropriés (h3, h4, h5 ou h6).';

// Chaînes de sévérité.
$string['severity_high'] = 'élevé';
$string['severity_medium'] = 'moyen';
$string['severity_low'] = 'faible';

// Chaînes de confidentialité.
$string['privacy:metadata:userid'] = 'L\'identifiant de l\'utilisateur demandant des corrections d\'accessibilité';
$string['privacy:metadata:content'] = 'Le contenu HTML analysé pour les problèmes d\'accessibilité';
$string['privacy:metadata:aiprovider'] = 'Le contenu est envoyé au fournisseur d\'IA configuré pour traitement';

