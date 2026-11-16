<?php
/**
 * Ninja Tables and Ninja Charts: the business's tables (a price list, a size guide, a course outline) and charts of
 * its numbers (data.php's 'tables'), made with the plugins' own importer, column settings and chart model, as their
 * admin screens save them. Each chart reads its rows from its own Ninja table, so editing the table moves the chart.
 * pages.php puts each one on the page it names ($sample['made']['tables']).
 */

use NinjaCharts\App\Models\NinjaCharts;
use NinjaTables\App\Models\Post as NinjaTablesPost;
use NinjaTables\App\Services\Import\TableImporter;

$importer = new TableImporter();
$made = [];
foreach ($sample['tables'] as $n => $table) {
    $id = (int) $importer->createTable(['post_title' => $table['title'], 'post_content' => $table['text'], 'post_type' => 'ninja-table', 'post_status' => 'publish']);
    if (!$id) {
        continue;
    }
    $header = ninja_table_format_header(array_keys($table['columns']));
    $importer->storeTableConfigWhenImporting($id, $header, 'footable');
    // The column types, as the table's column settings save them: a chart can only measure a number column.
    $types = array_combine(array_keys($header), array_values($table['columns']));
    $columns = array_map(static fn ($c) => ['data_type' => $types[$c['key']]] + $c, (array) get_post_meta($id, '_ninja_table_columns', true));
    NinjaTablesPost::updatedSettings($id, $columns, false);
    $importer->writeIndexedRows($id, 'footable', array_keys($header), $table['rows']);

    $chart = 0;
    if (!empty($table['chart'])) {
        $keys = array_map(static fn ($key) => ['key' => $key, 'label' => $header[$key], 'data_type' => $types[$key]], array_keys($header));
        // The first column names each bar, slice or point; the number columns after it are the series.
        $series = array_slice($keys, 1);
        // The theme's own colours, so the chart sits with the page (a palette change in the Site Editor leaves the
        // chart's saved colours as they are; the owner can change them on the chart's Design tab).
        $palette = array_column(wp_get_global_settings(['color', 'palette', 'theme']) ?: [], 'color', 'slug');
        $colours = array_values(array_filter([$palette['primary'] ?? null, $palette['primary-alt'] ?? null, $palette['secondary'] ?? null, $palette['primary-accent'] ?? null])) ?: ['#2747b8', '#f0b93a', '#4b505c', '#e3e9fb'];
        $values = array_map(static fn ($row) => array_slice($row, 1), $table['rows']);
        $round = in_array($table['chart'], ['pie', 'doughnut'], true);
        $datasets = [];
        foreach ($series as $s => $key) {
            $colour = $colours[$s % count($colours)];
            $datasets[] = [
                'label' => $key['label'],
                // Pie and doughnut charts colour each slice; the others colour each series.
                'backgroundColor' => $round ? array_map(static fn ($i) => $colours[$i % count($colours)], array_keys($table['rows'])) : $colour,
                'pointBackgroundColor' => 'white',
                'borderWidth' => 1,
                'borderColor' => $round ? '#ffffff' : $colour,
                'data' => array_map(static fn ($v) => $v[$s], $values),
            ];
        }
        $saved = NinjaCharts::store(ninjaChartsSanitizeArray([
            'chart_name' => $table['title'],
            'render_engine' => 'chart_js',
            'chart_type' => $table['chart'],
            'data_source' => 'ninja_table',
            'table_id' => $id,
            'final_keys' => $keys,
            // Every row of the table ('0'), so a row the owner adds shows on the chart too.
            'row' => ['number' => '0', 'date_range' => '', 'pick_range' => 'false', 'pick_date' => 'false', 'order' => 'ASC'],
            'options' => [
                'chart' => ['width' => '600', 'responsive' => 'true', 'height' => '400', 'position' => 'center', 'backgroundColor' => '#ffffff', 'borderWidth' => '0', 'borderColor' => '#000000', 'borderRadius' => '0', 'fontSize' => '12', 'fontStyle' => 'normal', 'fontColor' => '#000000', 'fill' => 'origin'],
                'animation' => 'linear',
                'series' => array_map(static fn ($key, $s) => ['label' => $key['label'], 'color' => $colours[$s % count($colours)], 'lineWidth' => '2'], $series, array_keys($series)),
                'axes' => ['display' => $round ? 'false' : 'true', 'stacked' => 'false', 'x_axis_label' => ' ', 'y_axis_label' => ' ', 'vertical_min_tick' => '', 'vertical_max_tick' => ''],
                // The page's heading names the chart, so the chart doesn't repeat it.
                'title' => ['display' => 'false', 'position' => 'top', 'fontStyle' => 'normal', 'fontColor' => '#000000', 'fontSize' => '16'],
                'tooltip' => ['enabled' => 'true', 'mode' => 'false', 'backgroundColor' => '#ffffff', 'titleFontSize' => '12', 'titleFontColor' => '#000000', 'bodyFontColor' => '#000000', 'bodyFontSize' => '12', 'displayColors' => 'true', 'borderColor' => '#cccccc', 'borderWidth' => '1'],
                'legend' => ['display' => 'true', 'position' => 'top', 'fontColor' => '#000000'],
                'layout' => ['padding' => ['left' => '0', 'right' => '0', 'top' => '0', 'bottom' => '0']],
            ],
            'datasets' => wp_json_encode($datasets),
        ]));
        $chart = (int) ($saved->id ?? 0);
    }
    $made[] = ['page' => $table['page'], 'title' => $table['title'], 'text' => $table['text'], 'table' => $id, 'chart' => $chart];
}
$sample['made']['tables'] = $made;
