/**
 * JavaScript module for accessibility fixer in HTML editor.
 *
 * @package    aiplacement_a11y
 * @copyright  2026 Patrick Thibaudeau, York University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Patrick Thibaudeau
 */

import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import Templates from 'core/templates';

/**
 * Accessibility Fixer integration for TinyMCE editor.
 */
export class A11yFixer {

    /**
     * Initialize the accessibility fixer.
     *
     * @param {Object} options Configuration options
     * @param {number} options.contextId The Moodle context ID
     * @param {Object} options.editor The TinyMCE editor instance
     */
    static init(options) {
        this.contextId = options.contextId;
        this.editor = options.editor;
        this.addToolbarButton();
    }

    /**
     * Add a button to the TinyMCE toolbar.
     *
     * @private
     */
    static addToolbarButton() {
        if (!this.editor) {
            return;
        }

        // Register the button
        this.editor.ui.registry.addButton('a11y_fix_accessibility', {
            text: 'Fix A11y',
            tooltip: 'Fix accessibility issues (WCAG AA)',
            icon: 'accessibility',
            onAction: () => this.openFixDialog(),
        });
    }

    /**
     * Open the accessibility fixing dialog.
     *
     * @private
     */
    static async openFixDialog() {
        const content = this.editor.getContent();

        if (!content || content.trim() === '') {
            Notification.addNotification({
                message: getString('nocontent', 'editor'),
                type: 'warning',
            });
            return;
        }

        this.showLoadingDialog();

        try {
            const result = await this.callFixAccessibility(content);
            this.showResultsDialog(result);
        } catch (error) {
            Notification.addNotification({
                message: `Error: ${error.message}`,
                type: 'error',
            });
        }
    }

    /**
     * Show a loading dialog while processing.
     *
     * @private
     */
    static showLoadingDialog() {
        const loadingHtml = `
            <div class="a11y-loading">
                <div class="spinner"></div>
                <p>Analyzing content for accessibility issues...</p>
            </div>
        `;

        const dialog = document.createElement('div');
        dialog.innerHTML = loadingHtml;
        dialog.className = 'a11y-dialog-loading';
        dialog.id = 'a11y-loading-dialog';

        document.body.appendChild(dialog);
    }

    /**
     * Hide the loading dialog.
     *
     * @private
     */
    static hideLoadingDialog() {
        const dialog = document.getElementById('a11y-loading-dialog');
        if (dialog) {
            dialog.remove();
        }
    }

    /**
     * Call the fix_accessibility web service.
     *
     * @private
     * @param {string} htmlContent The HTML content to fix
     * @return {Promise} Promise resolving to the API response
     */
    static callFixAccessibility(htmlContent) {
        return new Promise((resolve, reject) => {
            fetchMany([
                {
                    methodname: 'aiplacement_a11y_fix_accessibility',
                    args: {
                        contextid: this.contextId,
                        htmlcontent: htmlContent,
                    },
                },
            ])[0]
                .then((result) => {
                    this.hideLoadingDialog();
                    resolve(result);
                })
                .catch((error) => {
                    this.hideLoadingDialog();
                    reject(error);
                });
        });
    }

    /**
     * Show the results dialog with comparison view.
     *
     * @private
     * @param {Object} result The API response result
     */
    static async showResultsDialog(result) {
        const htmlContent = await this.buildResultsHtml(result);

        const modal = await ModalFactory.create({
            type: ModalFactory.types.LARGE,
            title: 'Accessibility Fix Results',
            body: htmlContent,
            large: true,
        });

        modal.getRoot().then((root) => {
            root.addEventListener('click', (e) => {
                if (e.target.matches('[data-action="accept-changes"]')) {
                    this.acceptChanges(result);
                    modal.hide();
                } else if (e.target.matches('[data-action="reject-changes"]')) {
                    modal.hide();
                }
            });
        });

        modal.show();
    }

    /**
     * Build HTML for the results dialog using Mustache template.
     *
     * @private
     * @param {Object} result The API response result
     * @return {Promise<string>} Promise resolving to HTML content for the dialog
     */
    static async buildResultsHtml(result) {
        // Debug: Log that we're using the tabbed version
        console.log('A11y Fixer v1.1.0 - Bootstrap 5 tabs', result);

        // Prepare context for template
        const context = {
            issues_found: result.issues_found,
            has_issues: result.has_issues,
            status_message: result.has_issues ?
                'Issues were found and fixed' :
                'No issues found',
            analysis_report: result.analysis_report || '',
            original_content: result.original_content || '',
            fixed_content: result.fixed_content || '',
            changes: []
        };

        // Parse changes if available
        if (result.changes_made && result.changes_made.length > 0) {
            try {
                context.changes = JSON.parse(result.changes_made);
            } catch (e) {
                console.error('Failed to parse changes:', e);
            }
        }

        console.log('Template context:', context);
        console.log('Calling Templates.render with template: aiplacement_a11y/fix_accessibility_results');

        // Render template
        const html = await Templates.render('aiplacement_a11y/fix_accessibility_results', context);

        console.log('Template rendered HTML (first 500 chars):', html.substring(0, 500));
        console.log('Check for Bootstrap tabs in HTML:', html.includes('nav nav-tabs') ? 'YES ✓' : 'NO ✗');
        console.log('Check for data-bs-toggle:', html.includes('data-bs-toggle') ? 'YES ✓' : 'NO ✗');

        return html;
    }


    /**
     * Accept the fixed content and update editor.
     *
     * @private
     * @param {Object} result The API response result
     */
    static acceptChanges(result) {
        this.editor.setContent(result.fixed_content);

        Notification.addNotification({
            message: 'Accessibility fixes applied successfully',
            type: 'success',
        });
    }

}

export default A11yFixer;
