VysokeSkoly/SolrFeeder
======================

[![Build Status](https://travis-ci.org/vysokeskoly/SolrFeeder.svg?branch=master)](https://travis-ci.org/vysokeskoly/SolrFeeder)
[![Coverage Status](https://coveralls.io/repos/github/vysokeskoly/SolrFeeder/badge.svg?branch=master)](https://coveralls.io/github/vysokeskoly/SolrFeeder?branch=master)

Data feeder for SOLR

## Installation
```bash
$ git clone https://github.com/vysokeskoly/SolrFeeder.git
  
$ cd SolrFeeder    
$ composer install --no-dev
```

## Requirements
- `php7.3`

## How to run it?

### Show list of available commands
```bash
bin/Solr-feeder-console list
```

### Usage:
```bash
bin/Solr-feeder-console [command] [arguments]
```

#### Available commands:
      help              Displays help for a command
      list              Lists commands
     Solr-feeder
      Solr-feeder:feed  Feed data from database to SOLR by xml configuration

### Feed
Feed data from `database` to `SOLR` by **xml configuration**

#### Usage:
```bash
bin/Solr-feeder-console Solr-feeder:feed [configPath]
```

#### Help:
    Arguments:
      config                Path to xml config file.
        
    Options:
      -h, --help            Display this help message
      -q, --quiet           Do not output any message
      -V, --version         Display this application version
          --ansi            Force ANSI output
          --no-ansi         Disable ANSI output
      -n, --no-interaction  Do not ask any interactive question
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug


### Xml Configuration
_You can find different examples in [Fixtures](https://github.com/vysokeskoly/SolrFeeder/tree/master/tests/Fixtures)_

```xml
<?xml version="1.0" encoding="UTF-8"?>
<ConnectorConfig xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="file:///C:/apps/jaxb-ri-2.2.7/bin/config.xsd">
  <lockFile>var/tmp/vysokeskoly.txt</lockFile>
  <statusReportFile>var/status/status-report-vysokeskoly.txt</statusReportFile>
  <log4jConfig>etc/log4j-vysokeskoly.properties</log4jConfig>
  <db>
    <driver>org.postgresql.Driver</driver>
    <connection>jdbc:postgresql://dbvysokeskoly:5432/vysokeskoly</connection>
    <user>vysokeskoly</user>
    <password>vysokeskoly</password>

    <credentialsFile>etc/db-credentials-vysokeskoly.xml</credentialsFile>

    <timestamps file="var/timestamp/last-timestamps.xml" >
        <timestamp type="datetime" name="timestamp" column="ts" lastValuePlaceholder="%%LAST_TIMESTAMP%%" currValuePlaceholder="%%CURRENT_TIMESTAMP%%" default="1970-01-01 00:00:00"/>
        <timestamp type="datetime" name="updated" column="updated" lastValuePlaceholder="%%LAST_UPDATED%%" currValuePlaceholder="%%CURRENT_UPDATED%%" default="1970-01-01 00:00:00"/>
        <timestamp type="datetime" name="deleted" column="deleted" lastValuePlaceholder="%%LAST_DELETED%%" currValuePlaceholder="%%CURRENT_DELETED%%" default="1970-01-01 00:00:00"/>
    </timestamps>

    <feeding>
      <feedingBatch name="add" type="add">
        <idColumn>study_id</idColumn>
        <mainSelect><![CDATA[
          SELECT *
          FROM studies_solr
          WHERE updated >= %%LAST_UPDATED%%
          ORDER BY updated ASC 
            ]]>
        </mainSelect>
        <columnMap>
            <map src="study_keyword" dst="study_keyword" separator="\|" />
            <map src="study_name" dst="study_name" />
            <map src="study_name" dst="study_name_str" />
            <map src="updated" dst="_ignored" /><!-- will be stored in lastmodified field - see the SQL query above -->
        </columnMap>
      </feedingBatch>

      <feedingBatch name="delete" type="delete">
        <idColumn>study_id</idColumn>
        <mainSelect><![CDATA[
          SELECT study_id, deleted FROM studies_solr WHERE deleted >= %%LAST_DELETED%%
         ]]></mainSelect>


      </feedingBatch>

    </feeding>


  </db>

  <feeder>
      <solr>
        <url>http://solr:8983/solr/vysokeskoly</url>
        <connectionType>http</connectionType>
        <readTimeout>200000</readTimeout>
        <batchSizeDocs>100</batchSizeDocs>
      </solr>
  </feeder>

</ConnectorConfig>
```

| Node | Description |
|------|-------------|
| `/lockFile` | Path to file which locks command from executing simultaneously | 
| `/statusReportFile` | Path to file which is updated every time commands run and holds a result (`0 2017-08-11T17:59:14 OK`) | 
| &nbsp;&nbsp; `0` | is exit status of command | 
| &nbsp;&nbsp; `timestamp` | of execution | 
| &nbsp;&nbsp; `OK` | is result (or error message) | 
| `/db/driver,connection,user,password` | Holds information about database connection (currently supported: `mysql`, `postgresql`) | 
| `/db/timestamps` |  | 
| &nbsp;&nbsp; `@file` | Path to file containing timestamps of last executed batch (_[see syntax](#timestamps-xml)_) | 
| &nbsp;&nbsp; `/timestamp` | Placeholders which will be replaced in `sql` query by previous values (_or by defaults_) | 
| `/db/feeding/feedingBatch` | Definition of one batch to Solr (currently supported types: `add`, `delete`) | 
| &nbsp;&nbsp; `/idColumn` | Column in `sql` which holds Solr primary key | 
| &nbsp;&nbsp; `/mainSelect` | `sql` query representing the batch data (_it is advised be ordered by column defined in `timestamps`_) |
| &nbsp;&nbsp; `/columnMap` | `sql` data mappings |
| &nbsp;&nbsp;&nbsp;&nbsp; `/map` | Mapping is mainly used for separating values for Solr `multi-valued field` |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; `@src` | `column` in row from database, which should be mapped (_mapped columns will **NOT** be send to Solr, unless they appear in `dst` too_) |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; `@dst` | field name in Solr document |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; `@separator` | [_OPTIONAL_] separator for data in `src` (_if it is not set, mapping will simply pass the value_) |
| `/feeder/solr` | | 
| &nbsp;&nbsp;`/url` | Solr collection url | 
| &nbsp;&nbsp;`/readTimeout` | Timeout for Solr client | 
| &nbsp;&nbsp;`/batchSizeDocs` | Max number of documents send to Solr in one batch | 


#### Timestamps xml
```xml
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<timestamps updatedOn="2017-08-07 21:12:03.347+0200">
    <timestamp name="deleted">2017-07-13 09:08:59.78</timestamp>
    <timestamp name="updated">2017-08-07 04:11:27.855</timestamp>
    <timestamp name="timestamp">1970-01-01 00:00:00.0</timestamp>
</timestamps>
```
