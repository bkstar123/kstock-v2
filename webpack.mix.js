const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/cms-assets/js/app.js', 'public/cms-assets/js')
    .js('resources/cms-assets/js/vue-app.js', 'public/cms-assets/js')
    .scripts([
        'resources/cms-assets/js/plugins/multiselect/multiselect.min.js',
        'resources/cms-assets/js/plugins/notifyjs/notify.min.js', // using notifyJS from https://notifyjs.jpillora.com
    ], 'public/cms-assets/js/plugins.js')
    .scripts([
    	'resources/cms-assets/js/stock-symbols/graph-reports/profitability_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/liquidity_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/cashflow_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/capex_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/effectiveness_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/income_statement.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/cash_flow_statement.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/financial_leverage_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/growth_ratios.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/current_assets_structure.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/long_term_assets_structure.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/assets_structure.js',
        'resources/cms-assets/js/stock-symbols/graph-reports/dupont_level5.js',
    ], 'public/js/stock-symbols/graph_report.min.js')
    .sourceMaps()
    .sass('resources/cms-assets/sass/app.scss', 'public/cms-assets/css')
    .copy('resources/cms-assets/img', 'public/cms-assets/img');

if (mix.inProduction()) {
    mix.version();
}