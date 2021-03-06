const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require('path');

module.exports = {
  entry: {
      style: "./assets/style/main.scss",
  },
  module: {
    rules: [
      {
        test: /\.scss$/i,
        use: [
	  MiniCssExtractPlugin.loader,
	  "css-loader",
	  "sass-loader",
	],
      },
      {
	test: /\.(eot|svg|ttf|svg)/,
	type: 'asset/resource',
      },
    ],
  },
  output: {
    path: path.resolve(__dirname, 'public/assets'),
  },
  plugins: [new MiniCssExtractPlugin()],
};
