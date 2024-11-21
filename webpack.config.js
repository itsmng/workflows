const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './js/workflow.js',
  output: {
    filename: 'bundle.js',
    path: path.resolve(__dirname, 'dist'), // Adjust the output directory as needed
  },
  mode: 'development',
  resolve: {
    alias: {
      'bpmn-client': path.resolve(__dirname, 'libs/bpmn-client/dist/index.js'), // Point to the local lib
    },
    fallback: {
      https: require.resolve('https-browserify'),
      http: require.resolve('stream-http'),
      fs: false,
      url: require.resolve('url/'),
      buffer: require.resolve('buffer/'),
    },
  },
  plugins: [
    new webpack.ProvidePlugin({
      Buffer: ['buffer', 'Buffer'],
    }),
  ],
};

