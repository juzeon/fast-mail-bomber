<?php
//If set to true, a provider will be added to dead list if it has a empty node list when updating nodes. Recommend: false
define('PRESERVE_EMPTY_PROVIDERS',false);

//The total number of nodes to use in a bombing action. Set to INF to use all nodes.
define('USE_NODES_COUNT',INF);

//The count of http connection threads to use in a bombing action. Ensure it suits your network environment.
define('CONCURRENCY',20);

//The Shodan api key to use when updating providers. Shodan provides free api access for the first 100 results: https://account.shodan.io/
define('SHODAN_API_KEY','');

//The proxy for all http requests to use. Note that in a bombing action your ip address will be disclosed to the target. Please ensure that you hide your real ip address properly.
define('PROXY','');

//The timeout value for all http requests.
define('TIMEOUT',30);

//Do not change the values below except for special needs.
define('PROVIDERS_JSON',__DIR__.'/data/providers.json');
define('DEAD_PROVIDERS_JSON',__DIR__.'/data/dead_providers.json');
define('NODES_JSON',__DIR__.'/data/nodes.json');