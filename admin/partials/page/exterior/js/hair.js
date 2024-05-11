/***
 * 愚人节彩蛋 - 你屏幕上有根毛
 * 出处：https://www.baidu.com/s?ie=UTF-8&wd=%E6%84%9A%E4%BA%BA%E8%8A%82
 * 整理：mengkun https://mkblog.cn/
 */

const bottom = Math.floor(60 * Math.random()),
  right = Math.floor(50 * Math.random()),
  rotate = Math.floor(360 * Math.random());
const foolsEgg = document.createElement("img");
//添加图片
foolsEgg.src = image_url;

foolsEgg.style.position = "fixed";
foolsEgg.style.bottom = `${bottom}%`;
foolsEgg.style.right = `${right}%`;
foolsEgg.style.zIndex = "9999";
foolsEgg.style.pointerEvents = "none";
foolsEgg.style.width = "40%";
foolsEgg.style.maxWidth = "190px";
foolsEgg.style.transform = "".concat(`rotate(${rotate}deg)`);
document.body.append(foolsEgg);
