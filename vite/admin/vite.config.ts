import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
//配置路径
import path from "path";
// 引入rollup-plugin-visualizer模块
import { visualizer } from "rollup-plugin-visualizer";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    react(),
    //打包分析
    //visualizer({
    //  open: true, //注意这里要设置为true，否则无效
    //  filename: "stats.html", //分析图生成的文件名
    //  gzipSize: true, // 收集 gzip 大小并将其显示
    //  brotliSize: true, // 收集 brotli 大小并将其显示
    //}),
  ],
  build: {
    rollupOptions: {
      output: {
        // 指定 chunk 文件名（含导出的代码）
        //chunkFileNames: 'js/[name].js',
        // 指定静态资源文件名（不含导出的代码）
        //assetFileNames: 'assets/[name].[ext]',
        entryFileNames: "index.js",
        assetFileNames: "[name][extname]",
        chunkFileNames: "[name].js",
      },
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
    },
  },
});
