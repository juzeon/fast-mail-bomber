<?php
// If set to false, a provider will be added to dead list if it has a empty node list when updating nodes. Recommend: false
// 如果设置为false，当一个提供者返回空列表（但是可以访问）的时候，会被添加到排除列表。建议：false
define('PRESERVE_EMPTY_PROVIDERS',false);

// The total number of nodes to use in a bombing action. Set to INF to use all nodes.
// 一次轰炸时所用的最大接口节点数量，设置为INF表示不限制
define('USE_NODES_COUNT',5000);

// The count of http connection threads to use in a bombing action. Ensure it suits your network environment.
// 轰炸时HTTP最大并发连接数，确保数值适合您的网络环境
define('CONCURRENCY',50);

// The size of thread pool when executing update-nodes
// 执行 update-nodes 命令时的线程池大小
define('THREAD_POOL_SIZE',20);

// (Optional) The Shodan api key to use when updating providers. Shodan provides free api access for the first 100 results: https://account.shodan.io/
// （可选）Shodan api key，在更新提供者的时候用。申请地址：https://account.shodan.io/
define('SHODAN_API_KEY','');

// (Optional) The ZoomEye api key to use when updating providers. ZoomEye provides free api access 10,000 results per month: https://www.zoomeye.org/profile/info
// （可选）ZoomEye api key，在更新提供者的时候用。地址：https://www.zoomeye.org/profile/info
define('ZOOMEYE_API_KEY','');

// Page limit of ZoomEye when updating providers. Set to INF to fetch all pages until the limit of api is reached.
// 更新ZoomEye时候限制的页面数量，INF为不限制，直到配额用完
define('ZOOMEYE_PAGE_LIMIT',50);

// The proxy for all http requests to use. Note that in a bombing action your ip address will be disclosed to the target. Please ensure that you hide your real ip address properly.
// Example #1 for HTTP proxy:
// 127.0.0.1:7890
// Example #2 for socks5 proxy (Yes, 'socks5h', not 'socks5'):
// socks5h://127.0.0.1:10808
// 对所有HTTP连接使用的代理配置。注意：在轰炸时，访问接口节点的ip地址会被暴露写到邮件里，所以请务必使用代理
// 例子1：如果是HTTP代理：
// 127.0.0.1:7890
// 例子2：如果是socks5代理（要写socks5h，不是socks5）：
// socks5h://127.0.0.1:10808
define('PROXY','');

// The timeout value for all http requests.
// 对所有HTTP连接的超时时间
define('TIMEOUT',30);

// Do not change the values below except for special needs.
// 以下内容除非特别需要，不宜更改
define('PROVIDERS_JSON',__DIR__.'/data/providers.json');
define('DEAD_PROVIDERS_JSON',__DIR__.'/data/dead_providers.json');
define('NODES_JSON',__DIR__.'/data/nodes.json');
define('REFINED_NODES_JSON',__DIR__.'/data/refined_nodes.json');
define('TEST_DIR',__DIR__.'/test');