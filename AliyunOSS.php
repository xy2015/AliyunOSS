<?php

namespace JohnLui\AliyunOSS;

require_once __DIR__.'/oss/aliyun.php';

use Aliyun\OSS\OSSClient;
use Aliyun\OSS\Models\OSSOptions;

/**
* \OssService
*/
class AliyunOSS {

  protected $ossClient;
  protected $bucket;

  public function __construct($serverName, $AccessKeyId, $AccessKeySecret)
  {
    $this->ossClient = OSSClient::factory([
      OSSOptions::ENDPOINT => $serverName,
      'AccessKeyId' => $AccessKeyId,
      'AccessKeySecret' => $AccessKeySecret
    ]);
  }

  public static function boot($serverName, $AccessKeyId, $AccessKeySecret)
  {
    return new AliyunOSS($serverName, $AccessKeyId, $AccessKeySecret);
  }

  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
    return $this;
  }

  public function uploadFile($key, $file)
  {
    $handle = fopen($file, 'r');
    $value = $this->ossClient->putObject(array(
        'Bucket' => $this->bucket,
        'Key' => $key,
        'Content' => $handle,
        'ContentLength' => filesize($file)
    ));
    fclose($handle);
    return $value;
  }

  public function uploadContent($key, $content)
  {
    return $this->ossClient->putObject(array(
        'Bucket' => $this->bucket,
        'Key' => $key,
        'Content' => $content,
        'ContentLength' => strlen($content)
    ));
  }

  public function getUrl($key, $expire_time)
  {
    return $this->ossClient->generatePresignedUrl([
      'Bucket' => $this->bucket,
      'Key' => $key,
      'Expires' => $expire_time
    ]);
  }

  public function createBucket($bucketName)
  {
    return $this->ossClient->createBucket(['Bucket' => $bucketName]);
  }

  public function getAllObjectKey($bucketName)
  {
    $objectListing = $this->ossClient->listObjects(array(
      'Bucket' => $bucketName,
    ));

    $objectKeys = [];
    foreach ($objectListing->getObjectSummarys() as $objectSummary) {
      $objectKeys[] = $objectSummary->getKey();
    }
    return $objectKeys;
  }

  /**
   * 获取指定文件夹下的所有文件
   *
   * @param string $bucketName 存储容器名称
   * @param string $folder_name 文件夹名
   * @return 指定文件夹下的所有文件
   */
  public function getAllObjectKeyWithPrefix($bucketName, $folder_name, $nextMarker='')
  {

    $objectKeys = [];

    while (true){
      $objectListing = $this->ossClient->listObjects(array(
        'Bucket' => $bucketName,
        'Prefix' => $folder_name,
        'MaxKeys' => 1000,
        'Marker' => $nextMarker,
      ));

      foreach ($objectListing->getObjectSummarys() as $objectSummary) {
        $objectKeys[] = $objectSummary->getKey();
      }

      $nextMarker = $objectListing->getNextMarker();
      if ($nextMarker === '' || is_null($nextMarker)) {
        break;
      }
    }
    return $objectKeys;
  }
    
  /**
   * 删除阿里云中存储的文件
   *
   * @param string $bucketName 存储容器名称
   * @param string $key 存储key（文件的路径和文件名）
   * @return void
   */
  public function deleteObject($bucketName, $key)
  {
      if ($bucketName === null) {
          $bucketName = $this->bucket;
      }
      return $this->ossClient->deleteObject([
          'Bucket'    => $bucketName,
          'Key'       => $key
      ]);
  }

  /**
   * 复制存储在阿里云OSS中的Object
   *
   * @param string $sourceBuckt 复制的源Bucket
   * @param string $sourceKey - 复制的的源Object的Key
   * @param string $destBucket - 复制的目的Bucket
   * @param string $destKey - 复制的目的Object的Key
   * @return Models\CopyObjectResult
   */
  public function copyObject($sourceBuckt, $sourceKey, $destBucket, $destKey)
  {
      if ($sourceBuckt === null) {
          $sourceBuckt = $this->bucket;
      }
      if ($destBucket === null) {
          $destBucket = $this->bucket;
      }
      return $this->ossClient->copyObject([
          'SourceBucket'  => $sourceBuckt,
          'SourceKey'     => $sourceKey,
          'DestBucket'    => $destBucket,
          'DestKey'       => $destKey
      ]);
  }

  /**
   * 移动存储在阿里云OSS中的Object
   *
   * @param string $sourceBuckt 复制的源Bucket
   * @param string $sourceKey - 复制的的源Object的Key
   * @param string $destBucket - 复制的目的Bucket
   * @param string $destKey - 复制的目的Object的Key
   * @return Models\CopyObjectResult
   */
  public function moveObject($sourceBuckt, $sourceKey, $destBucket, $destKey)
  {
      if ($sourceBuckt === null) {
          $sourceBuckt = $this->bucket;
      }
      if ($destBucket === null) {
          $destBucket = $this->bucket;
      }

      $result = $this->ossClient->copyObject([
          'SourceBucket'  => $sourceBuckt,
          'SourceKey'     => $sourceKey,
          'DestBucket'    => $destBucket,
          'DestKey'       => $destKey
      ]);

      if (is_object($result) && $result->getETag()) {
          $this->deleteObject($sourceBuckt, $sourceKey);
      }
      
      return $result;
  }
}
