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
- `php7.1`

## How to run it?

### Show list of available commands
```bash
bin/solr-feeder-console list
```

### Usage:
```bash
bin/solr-feeder-console [command] [arguments]
```

#### Available commands:
      help              Displays help for a command
      list              Lists commands
     solr-feeder
      solr-feeder:feed  Feed data from database to SOLR by xml configuration

### Feed
Feed data from `database` to `SOLR` by **xml configuration**

#### Usage:
```bash
bin/solr-feeder-console solr-feeder:feed [configPath]
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
_TODO_


## Todo

* finish documentation
