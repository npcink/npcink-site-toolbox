import { defineConfig, type Plugin } from "vite";
import react from "@vitejs/plugin-react";
//配置路径
import path from "path";

const isolateAdminBootstrap = (): Plugin => ({
  name: "mabox-isolate-admin-bootstrap",
  enforce: "post",
  generateBundle(_options, bundle) {
    const bootstrap = bundle["index.js"];
    if (!bootstrap || bootstrap.type !== "chunk" || !bootstrap.isEntry) {
      this.error("Admin bootstrap index.js was not emitted as an entry chunk");
    }
    if (bootstrap.dynamicImports.length !== 1) {
      this.error("Admin bootstrap must have exactly one dynamic app entry");
    }

    const appEntry = bootstrap.dynamicImports[0];
    bootstrap.code = `void import(${JSON.stringify(`./${appEntry}`)});\n`;
    bootstrap.imports = [];
  },
});

// https://vitejs.dev/config/
export default defineConfig({
  root: __dirname,
  plugins: [
    isolateAdminBootstrap(),
    react(),
  ],
  build: {
    outDir: path.resolve(__dirname, "dist"),
    emptyOutDir: true,
    manifest: ".vite/manifest.json",
    cssCodeSplit: false,
    modulePreload: false,
    rollupOptions: {
      input: {
        index: path.resolve(__dirname, "index.html"),
        app: path.resolve(__dirname, "src/main.tsx"),
      },
      output: {
        // 指定 chunk 文件名（含导出的代码）
        //chunkFileNames: 'js/[name].js',
        // 指定静态资源文件名（不含导出的代码）
        //assetFileNames: 'assets/[name].[ext]',
        entryFileNames: (chunkInfo) =>
          chunkInfo.name === "index"
            ? "index.js"
            : "assets/[name]-[hash].js",
        assetFileNames: (assetInfo) =>
          assetInfo.name?.endsWith(".css")
            ? "index.css"
            : "assets/[name]-[hash][extname]",
        chunkFileNames: "assets/[name]-[hash].js",
      },
    },
    //sourcemap: true,//保留映射关系，方便调试
    chunkSizeWarningLimit: 900,
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
    },
  },
  // 资源相对于入口文件解析，兼容自定义 wp-content 与插件目录。
  base: "./",
      //代理
  server: {
    //host: "0.0.0.0",
    //port: 3000,
    //open: true,
    proxy: {
      "/api": {
        target: "http://localhost:10029/",
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ""),
      },
    },
  },
});
