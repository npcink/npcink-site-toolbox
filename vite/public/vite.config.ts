import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

//配置路径
import path from "path";

//媒体资源打包添加前缀
const site = "/wp-content/plugins/wp-magick-toolbox/vite/public/dist";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
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

        manualChunks: {
          // 公共依赖
          vendor: ["react", "react-dom", "antd"],
          // 分享功能主模块
          share: ["./src/components/share/index"],
          // html2canvas 独立分包（仅海报生成需要，~205KB）
          poster: ["html2canvas"],
          // 二维码组件独立分包
          qrcode: ["./src/components/share/QRcode"],
        },
      },
    },
   // sourcemap: true, //保留映射关系，方便调试
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
    },
  },
  //媒体资源打包前缀

  base: site,
});
