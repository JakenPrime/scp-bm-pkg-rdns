<?php

namespace Packages\Rdns\App\Ptr;

use Packages\Rdns\App\Ptr\DnsRecordService;

class PtrValidateRdns {

  /**
   * @var DnsRecordService
   */
  protected $dns;

  /**
   * @param DnsRecordService $dns
   */
  public function __construct(DnsRecordService $dns){
    $this->dns = $dns;
  }
    /**
     * @return bool
     */
    public function validate($ip, $domain) {
        $answers = $this->dns->getARecords($domain);
        foreach($answers as $record) {
            switch($record['type']) {
               case "A":
                   return $record['ip'] === $ip;
               case "AAAA":
                  return $record['ipv6'] === $ip;
            }
        }
    }
}