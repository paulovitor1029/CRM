<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Models\TenantConfig;
use App\Models\TenantCustomField;
use App\Models\TenantFeatureFlag;
use Illuminate\Database\DatabaseManager;

class TenantAdminService
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function setConfig(string $tenantId, string $scope, array $data, ?string $userId = null): TenantConfig
    {
        return $this->db->transaction(function () use ($tenantId, $scope, $data, $userId) {
            $current = TenantConfig::where('tenant_id',$tenantId)->where('scope',$scope)->first();
            $version = $current ? $current->version + 1 : 1;
            if ($current) {
                \DB::table('tenant_config_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'tenant_id' => $tenantId,
                    'scope' => $scope,
                    'version' => $version,
                    'before' => json_encode($current->data),
                    'after' => json_encode($data),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $current->fill(['data'=>$data,'version'=>$version,'updated_by'=>$userId])->save();
                return $current;
            }
            \DB::table('tenant_config_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'tenant_id' => $tenantId,
                'scope' => $scope,
                'version' => $version,
                'before' => null,
                'after' => json_encode($data),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            return TenantConfig::create(['tenant_id'=>$tenantId,'scope'=>$scope,'version'=>$version,'data'=>$data,'updated_by'=>$userId]);
        });
    }

    public function upsertCustomField(string $tenantId, array $payload, ?string $userId = null): TenantCustomField
    {
        return $this->db->transaction(function () use ($tenantId, $payload, $userId) {
            $field = TenantCustomField::where('tenant_id',$tenantId)->where('entity',$payload['entity'])->where('key',$payload['key'])->first();
            $after = $payload;
            if ($field) {
                $version = $field->version + 1;
                \DB::table('tenant_custom_field_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'field_id' => $field->id,
                    'version' => $version,
                    'before' => json_encode($field->toArray()),
                    'after' => json_encode($after),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $field->fill($payload + ['version'=>$version,'updated_by'=>$userId])->save();
                return $field;
            }
            $field = TenantCustomField::create($payload + ['tenant_id'=>$tenantId,'version'=>1,'updated_by'=>$userId]);
            \DB::table('tenant_custom_field_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'field_id' => $field->id,
                'version' => 1,
                'before' => null,
                'after' => json_encode($field->toArray()),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            return $field;
        });
    }

    public function setFeatureFlag(string $tenantId, string $flagKey, bool $enabled, ?string $userId = null): TenantFeatureFlag
    {
        return $this->db->transaction(function () use ($tenantId, $flagKey, $enabled, $userId) {
            $flag = TenantFeatureFlag::where('tenant_id',$tenantId)->where('flag_key',$flagKey)->first();
            if ($flag) {
                $version = $flag->version + 1;
                \DB::table('tenant_feature_flag_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'tenant_id' => $tenantId,
                    'flag_key' => $flagKey,
                    'version' => $version,
                    'enabled' => $enabled,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $flag->fill(['enabled'=>$enabled,'version'=>$version,'updated_by'=>$userId])->save();
                return $flag;
            }
            \DB::table('tenant_feature_flag_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'tenant_id' => $tenantId,
                'flag_key' => $flagKey,
                'version' => 1,
                'enabled' => $enabled,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            return TenantFeatureFlag::create(['tenant_id'=>$tenantId,'flag_key'=>$flagKey,'enabled'=>$enabled,'version'=>1,'updated_by'=>$userId]);
        });
    }

    public function upsertTemplate(string $tenantId, array $template, ?string $userId = null): MessageTemplate
    {
        return $this->db->transaction(function () use ($tenantId, $template, $userId) {
            $current = MessageTemplate::where('tenant_id',$tenantId)->where('channel',$template['channel'])->where('key',$template['key'])->first();
            if ($current) {
                $version = $current->version + 1;
                \DB::table('message_template_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'tenant_id' => $tenantId,
                    'channel' => $template['channel'],
                    'key' => $template['key'],
                    'version' => $version,
                    'before' => json_encode($current->toArray()),
                    'after' => json_encode($template),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $current->fill($template + ['version'=>$version,'updated_by'=>$userId])->save();
                return $current;
            }
            \DB::table('message_template_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'tenant_id' => $tenantId,
                'channel' => $template['channel'],
                'key' => $template['key'],
                'version' => 1,
                'before' => null,
                'after' => json_encode($template),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            return MessageTemplate::create($template + ['tenant_id'=>$tenantId,'version'=>1,'updated_by'=>$userId]);
        });
    }
}

