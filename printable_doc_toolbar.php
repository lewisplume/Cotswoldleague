<?php
/**
 * Shared secondary toolbar for printable gala document pages.
 *
 * Required:
 *   $printable_doc_title (string)
 *
 * Optional:
 *   $printable_doc_back_href (string, default teamportal.php#documents)
 *   $printable_doc_controls (string, HTML for centre controls)
 *   $printable_doc_extra_actions (string, HTML before the Print button)
 *   $printable_doc_print_label (string, default 'Print')
 *   $printable_doc_print_button_id (string)
 *   $printable_doc_print_button_class (string, extra classes on Print button)
 *   $printable_doc_show_print (bool, default true)
 */
$printable_doc_back_href = $printable_doc_back_href ?? 'teamportal.php#documents';
$printable_doc_controls = $printable_doc_controls ?? '';
$printable_doc_extra_actions = $printable_doc_extra_actions ?? '';
$printable_doc_print_label = $printable_doc_print_label ?? 'Print';
$printable_doc_print_button_id = $printable_doc_print_button_id ?? '';
$printable_doc_print_button_class = trim($printable_doc_print_button_class ?? '');
$printable_doc_show_print = $printable_doc_show_print ?? true;
?>
<div class="no-print border-b border-slate-800 bg-slate-900/90 backdrop-blur-md sticky top-16 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between py-4 gap-4">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <a href="<?php echo htmlspecialchars($printable_doc_back_href); ?>"
                    class="text-white hover:text-sky-400 transition-colors"
                    title="Back to Team Portal documents">
                    <i data-lucide="arrow-left" class="w-6 h-6"></i>
                </a>
                <span class="text-white font-bold text-lg"><?php echo htmlspecialchars($printable_doc_title); ?></span>
            </div>

            <?php if ($printable_doc_controls !== ''): ?>
            <div
                class="controls-container flex flex-wrap items-center justify-center gap-4 bg-slate-800 rounded-lg p-2 px-4 border border-slate-700 w-full md:w-auto">
                <?php echo $printable_doc_controls; ?>
            </div>
            <?php endif; ?>

            <?php if ($printable_doc_extra_actions !== '' || $printable_doc_show_print): ?>
            <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                <?php echo $printable_doc_extra_actions; ?>
                <?php if ($printable_doc_show_print): ?>
                <button type="button" onclick="window.print()"
                    id="<?php echo htmlspecialchars($printable_doc_print_button_id); ?>"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-full font-bold shadow-lg shadow-sky-500/30 flex-1 md:flex-none flex items-center justify-center gap-2 transition-all text-sm uppercase tracking-wider <?php echo htmlspecialchars($printable_doc_print_button_class); ?>">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span><?php echo htmlspecialchars($printable_doc_print_label); ?></span>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
