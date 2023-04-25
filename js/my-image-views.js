
console.log(imageViewsData)




const App = Vue.createApp({
    setup() {



        //拿到数据
        const dataList = imageViewsData.reverse();


        //替换数组
        const replaceId = [
            { id: '2', name: "五一投放图片展现广告" },
            { id: '2199', name: "五一投放图片点击广告" }
        ];


        //选择的结果值
        const selectedId = Vue.ref('All');
        //展示的筛选值
        const filteredData = Vue.computed(() => {

            if (selectedId.value === 'All') {
                const data = replace(dataList, replaceId);
                return data;
            } else {
                const arr = dataList.filter(item => item.id === selectedId.value);
                const data = replace(arr, replaceId);
                return data;
            }
        });

        //拿到ID组成选项数组
        const arrIdFn = () => {
            // 用 map 方法获取 ID 数组
            const idList = dataList.map(item => item.id);

            // 用 Set 数据结构进行去重
            const uniqueIdList = [...new Set(idList)];

            // 按从小到大的顺序排序
            uniqueIdList.sort((a, b) => a - b);
            //插入all
            uniqueIdList.unshift("All");
            return uniqueIdList;
        }
        const arrId = Vue.ref();
        arrId.value = arrIdFn();


        //添加ID对应计划关系
        const replace = (data, replaceId) => {
            const a = data;
            const b = replaceId;
            const bMap = new Map(b.map(item => [item.id, item.name]));
            a.forEach(item => {
                item.name = bMap.get(item.id) || '没有计划';
            });
            return a;
        }


        //按要求处理数组
        // { id: '2', date: '2023-04-20', count: '1', name: '五一投放图片展现广告' },
        const handleData = (data) => {

            //获取指定值构成的数组
            const getArr = (data, id) => {
                // 使用 reduce 函数进行去重
                const idList = data.reduce((prev, current) => {
                    if (!prev[current[id]]) {
                        prev[current[id]] = true;
                    }
                    return prev;
                }, {});
                // 将对象属性转换成数组返回
                return Object.keys(idList);
            };

            //获取数据集
            /*
            * data:待处理数据
            * id: id数组
            * date:时间数组
            */
            const arrData = (data, id, date) => {
                //存储结果
                const list = [];
                //拿到id
                id.forEach(item => {
                    const a = data.filter(index => index.id === item); // 筛选出所有id为指定值的数据 包含对象的数组
                    const result = date.map(date => {
                        // 使用 find 方法查找数据，如果找到了则返回对应的 count 值，否则为 0
                        const foundItem = a.find(index => index.date === date);
                        return foundItem ? Number(foundItem.count) : 0;
                    });

                    //组成数组
                    const arr = {
                        name: item,
                        data: result,
                        type: 'line',
                        smooth: true,
                    }
                    list.push(arr);
                })
                return list;
            }


            //获取ID数组
            const id = getArr(data, 'id');
            //获取时间数组
            const date = getArr(data, 'date').reverse();

            const list = arrData(data, id, date);

            //处理下时间
            const handleDate = date.map(item => item.substr(5).replace('-', '_'));



            const obj = {
                id: id,
                date: handleDate,
                data: list,
            }
            return obj;
        };




        //获取div节点
        const main = Vue.ref(null);
        //定义全局变量
        const chart = Vue.shallowRef();//这里使用shallowRef，避免进行标签筛选时报错
        //拿到数据并展示
        const showEcharts = (item) => {
            if (chart.value != null && chart.value != "" && chart.value != undefined) {
                chart.value.dispose();
            }

            // 基于准备好的dom，初始化echarts实例
            chart.value = echarts.init(main.value);
            // 指定图表的配置项和数据
            const option = {
                title: {
                    text: "广告效果统计",
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: item.id,
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                toolbox: {
                    feature: {
                        saveAsImage: {}
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: item.date,
                },
                yAxis: {
                    type: 'value'
                },
                series: item.data,
            };

            // 使用刚指定的配置项和数据显示图表。
            chart.value.setOption(option);

        }



        //开始准备图标
        Vue.onMounted(() => {
            //处理筛选后的数据
            const data = handleData(filteredData.value)
            //渲染图表
            showEcharts(data);

        });
        Vue.watch(() => filteredData.value, (newValue) => {
            //处理筛选后的数据
            const data = handleData(filteredData.value)

            //渲染图表
            showEcharts(data);
        })






        return {
            selectedId,
            filteredData,
            arrId,
            main,

        };

    },
    template: `
    ID 筛选
    <select title="选择需要筛选的ID" v-model="selectedId">
            <option v-for="(item,index) in arrId" :key="index" :value="item">{{item}}</option>
        </select>
    <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>计划</th>
                        <th>时间</th>
                        <th>展现次数</th>
                    </tr>
                </thead>
                <tbody v-for="(item,index) in filteredData" :key="index">
                    <tr>
                        <td>{{item.id}}</td>
                        <td>{{item.name}}</td>
                        <td>{{item.date}}</td>
                        <td>{{item.count}}</td>
                    </tr>
                </tbody>
            </table>
            <!-- 为 ECharts 准备一个定义了宽高的 DOM -->
            <br/>
        <div ref="main" style="width: 600px; height: 400px"></div>
    `
});
App.mount("#Application");




