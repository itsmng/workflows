const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './js/workflow.js', // Point d'entrée de ton application
  output: {
    filename: 'bundle.js', // Le fichier final généré par Webpack
    path: path.resolve(__dirname, 'dist'), // Répertoire de sortie
  },
  mode: 'development', // Mode de développement (utilise 'production' pour un build optimisé)
  resolve: {
    alias: {
      'bpmn-client': path.resolve(__dirname, 'libs/bpmn-client/dist/index.js'), // Alias pour bpmn-client
    },
    fallback: {
      https: require.resolve('https-browserify'), // Polyfill pour https
      http: require.resolve('stream-http'), // Polyfill pour http
      fs: false, // Désactiver le polyfill pour fs
      url: require.resolve('url/'), // Polyfill pour URL
      buffer: require.resolve('buffer/'), // Polyfill pour Buffer
    },
    extensions: ['.js', '.jsx'], // Ajout de l'extension JSX
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/, // Supporte les fichiers JS et JSX
        exclude: /node_modules/, // Ne pas transpiler les fichiers dans node_modules
        use: {
          loader: 'babel-loader', // Utilise Babel pour transpiler le code
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react'], // Support ES6+ et JSX
          },
        },
      },
    ],
  },
  plugins: [
    new webpack.ProvidePlugin({
      Buffer: ['buffer', 'Buffer'], // Fournir le polyfill pour Buffer globalement
    }),
  ],
  devtool: 'source-map', // Génère des cartes sources pour le débogage
};
