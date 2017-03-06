<?php
namespace CommunityTranslation\Tests\Api;

class StatsTest extends ApiTest
{
    public function testGetLocales()
    {
        $this->apiClient->setEntryPoint('locales')->exec();
        $this->assertSame(200, $this->apiClient->getLastResponseCode());
        $this->assertSame('application/json', $this->apiClient->getLastResponseType());
        $data = $this->apiClient->getLastResponseData();
        $this->assertInternalType('array', $data);
        if (empty($data)) {
            $this->markTestIncomplete('No locales defined in CommunityTranslation');
        }
        foreach ($data as $key => $value) {
            $this->assertInternalType('int', $key);
            $this->assertInternalType('array', $value);
            $this->assertArrayHasKey('id', $value);
            $this->assertArrayHasKey('name', $value);
        }
    }

    public function testGetAvailablePackages()
    {
        $this->apiClient->setEntryPoint('packages')->exec();
        $this->assertSame(200, $this->apiClient->getLastResponseCode());
        $this->assertSame('application/json', $this->apiClient->getLastResponseType());
        $data = $this->apiClient->getLastResponseData();
        $this->assertInternalType('array', $data);
        if (empty($data)) {
            $this->markTestIncomplete('No packages defined in CommunityTranslation');
        }
        foreach ($data as $key => $value) {
            $this->assertInternalType('int', $key);
            $this->assertInternalType('array', $value);
            $this->assertArrayHasKey('handle', $value);
            $this->assertArrayHasKey('name', $value);
        }
    }
}
