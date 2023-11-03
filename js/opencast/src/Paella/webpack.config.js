const path = require('path');

module.exports = {
    entry: './player.js',
    output: {
        path: path.resolve(__dirname),
        filename: 'paella-player.min.js',
        sourceMapFilename: 'paella-player.min.js.map',
        globalObject: 'this',
        library: {
            name: 'PaellaPlayer',
            type: 'umd'
        },

    },
    // devtool: 'source-map',
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            },

            {
                test: /\.svg$/i,
                use: {
                    loader: 'svg-inline-loader'
                }
            },
            {
                test: /\.css$/i,
                use: ['style-loader', 'css-loader']
            }
        ]
    }
};
