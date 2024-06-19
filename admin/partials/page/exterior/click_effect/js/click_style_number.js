jQuery(document).ready(function($) {  
    var _click_count=0;  
    $("body").bind("click",function(e){
        var n=Math.round(Math.random()*100)+1;//生成100以内的随机数
          if(n == 0) res= '0';
          var res = '';  
          while(n != 0) {
              res = n % 2 + res
              n = parseInt(n / 2)
          }//将n转化为二进制数res
        var $i=$("<b>").text("+"+(res));
        var x=e.pageX,y=e.pageY;//鼠标点击的坐标
        $i.css({  
            "z-index":99999,  
            "top":y-15,  
            "left":x,  
            "position":"absolute",  
            "color":"rgb("+~~(255*Math.random())+","+~~(255*Math.random())+","+~~(255*Math.random())+")"//颜色随机
            //"#2299DD"  //固定颜色
            });  
            $("body").append($i);  
            $i.animate({
                "top":y-180,
                "opacity":0
                },  
                1500,  
                function(){$i.remove();}  
            );  
            e.stopPropagation();  
        });  
    });  

