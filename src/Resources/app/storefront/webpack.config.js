const path = require("path")
const {resolve, join} = require("path");

module.exports = {
    mode: 'production',
    entry: './src/custom.js',
    output: {
        path: path.resolve(__dirname, 'dist', 'storefront', 'js'),
        filename: 'unzer-payment6.js',
    },
    resolve: {
        extensions: ['.js'],
        alias: {
            'src': resolve(
                join(__dirname, '..', '..', '..', '..', '..', '..', '..', 'vendor', 'shopware', 'storefront', 'Resources', 'app', 'storefront', 'src'),
            ),
        },
    },
}
