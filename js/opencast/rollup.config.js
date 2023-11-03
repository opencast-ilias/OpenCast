import {terser} from 'rollup-plugin-terser';
import commonjs from '@rollup/plugin-commonjs';
import { nodeResolve } from '@rollup/plugin-node-resolve';
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
    plugins: [terser(), commonjs(), nodeResolve()]
};
