import {terser} from 'rollup-plugin-terser';

export default {
    external: [
        'document',
        'ilias',
        'jquery',
    ],
    input: './src/index.js',
    output: {
        file: './dist/index.js',
        format: 'iife',
        globals: {
            document: 'document',
            ilias: 'il',
            jquery: '$',
        }
    },
    plugins: [terser()]
};
