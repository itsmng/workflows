const webpack = require('webpack');

module.exports = {
  entry: './js/workflow.js',
  output: {
    filename: 'bundle.js'
  },
  mode: 'development',
  resolve: {
    fallback: {
      https: require.resolve('https-browserify'),
      http: require.resolve('stream-http'),
      fs: false,
      url: require.resolve('url/'),
      buffer: require.resolve('buffer/')
    }
  },
  plugins: [
    new webpack.ProvidePlugin({
      Buffer: ['buffer', 'Buffer']
    })
  ]
};

