function install_es_plugins {
    ESVersion=$(/usr/share/elasticsearch/bin/elasticsearch -V|awk -F',' '{print $1}'| awk '{print $2}')

    [[ -e /usr/share/elasticsearch/plugins/analysis-ik ]] || {
        /usr/share/elasticsearch/bin/elasticsearch-plugin install --batch
         https://github.com/medcl/elasticsearch-analysis-ik/releases/download/v${ESVersion}/elasticsearch-analysis-ik-${ESVersion}.zip
    }
    mkdir -p /etc/elasticsearch/analysis/
    touch /etc/elasticsearch/analysis/synonyms.txt

    service elasticsearch restart
}
