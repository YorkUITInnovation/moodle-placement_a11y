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
        const htmlContent = this.buildResultsHtml(result);

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
                } else if (e.target.matches('[data-toggle="comparison"]')) {
                    this.toggleComparison(e.target);
                }
            });
        });

        modal.show();
    }

    /**
     * Build HTML for the results dialog.
     *
     * @private
     * @param {Object} result The API response result
     * @return {string} HTML content for the dialog
     */
    static buildResultsHtml(result) {
        let html = '<div class="a11y-results">';

        // Summary section.
        html += '<div class="a11y-summary">';
        html += `<p><strong>Issues Found:</strong> ${result.issues_found}</p>`;
        html += `<p><strong>Status:</strong> ${result.has_issues ? 'Issues were found and fixed' : 'No issues found'}</p>`;
        html += '</div>';

        // Analysis report.
        if (result.analysis_report) {
            html += '<div class="a11y-report">';
            html += result.analysis_report;
            html += '</div>';
        }

        // Comparison view.
        if (result.has_issues) {
            html += '<div class="a11y-comparison">';
            html += '<button type="button" data-toggle="comparison" class="btn btn-link">';
            html += 'Show Original vs. Fixed Comparison';
            html += '</button>';

            html += '<div class="comparison-content hidden">';
            html += '<div class="comparison-original">';
            html += '<h4>Original</h4>';
            html += '<pre>' + this.escapeHtml(result.original_content) + '</pre>';
            html += '</div>';

            html += '<div class="comparison-fixed">';
            html += '<h4>Fixed</h4>';
            html += '<pre>' + this.escapeHtml(result.fixed_content) + '</pre>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            // Changes list.
            if (result.changes_made && result.changes_made.length > 0) {
                const changes = JSON.parse(result.changes_made);
                html += '<div class="a11y-changes">';
                html += '<h4>Changes Made</h4>';
                html += '<ul>';
                changes.forEach((change) => {
                    html += `<li>${change.before} → ${change.after}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            }

            // Action buttons.
            html += '<div class="a11y-actions">';
            html += '<button type="button" data-action="accept-changes" class="btn btn-primary">';
            html += 'Accept Changes';
            html += '</button>';
            html += '<button type="button" data-action="reject-changes" class="btn btn-secondary">';
            html += 'Reject Changes';
            html += '</button>';
            html += '</div>';
        }

        html += '</div>';

        return html;
    }

    /**
     * Toggle the comparison view.
     *
     * @private
     * @param {Element} button The toggle button
     */
    static toggleComparison(button) {
        const content = button.nextElementSibling;
        if (content) {
            content.classList.toggle('hidden');
            button.textContent = content.classList.contains('hidden')
                ? 'Show Original vs. Fixed Comparison'
                : 'Hide Comparison';
        }
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

    /**
     * Escape HTML for safe display in pre tag.
     *
     * @private
     * @param {string} text Text to escape
     * @return {string} Escaped HTML
     */
    static escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }
}

export default A11yFixer;
