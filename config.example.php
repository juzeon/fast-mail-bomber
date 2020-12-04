<?php
// If set to false, a provider will be added to dead list if it has a empty node list when updating nodes. Recommend: false
// 如果设置为false，当一个提供者返回空列表（但是可以访问）的时候，会被添加到排除列表。建议：false
define('PRESERVE_EMPTY_PROVIDERS',false);

// The total number of nodes to use in a bombing action. Set to INF to use all nodes.
// 一次轰炸时所用的最大接口节点数量，设置为INF表示不限制
define('USE_NODES_COUNT',INF);

// The count of http connection threads to use in a bombing action. Ensure it suits your network environment.
// 轰炸时HTTP最大并发连接数，确保数值适合您的网络环境
define('CONCURRENCY',20);

// The Shodan api key to use when updating providers. Shodan provides free api access for the first 100 results: https://account.shodan.io/
// Shodan api key，在更新提供者的时候用。申请地址：https://account.shodan.io/
define('SHODAN_API_KEY','');

// The proxy for all http requests to use. Note that in a bombing action your ip address will be disclosed to the target. Please ensure that you hide your real ip address properly.
// 对所有HTTP连接使用的代理配置。注意：在轰炸时，访问接口节点的ip地址会被暴露写到邮件里，所以请务必使用代理
define('PROXY','');

// The timeout value for all http requests.
// 对所有HTTP连接的超时时间
define('TIMEOUT',30);

// Do not change the values below except for special needs.
// 以下内容除非特别需要，不宜更改
define('PROVIDERS_JSON',__DIR__.'/data/providers.json');
define('DEAD_PROVIDERS_JSON',__DIR__.'/data/dead_providers.json');
define('NODES_JSON',__DIR__.'/data/nodes.json');