<?php declare(strict_types=1);

namespace Concept\Components\SettingsManager\Controllers;

use Concept\Common\Mappers\FormValueNormalizer;
use Concept\Components\SettingsManager\Constants\RouteName;
use Concept\Components\SettingsManager\Constants\ViewName;
use Concept\Components\SettingsManager\Dto\StoreSettingDto;
use Concept\Components\SettingsManager\Dto\UpdateSettingDto;
use Concept\Components\SettingsManager\Enums\SettingDataType;
use Concept\Components\SettingsManager\Enums\SettingGroup;
use Concept\Components\SettingsManager\Mappers\SettingFormValueMapper;
use Concept\Components\SettingsManager\Models\SettingModel;
use Concept\Components\SettingsManager\Requests\StoreSettingRequest;
use Concept\Components\SettingsManager\Requests\UpdateSettingRequest;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\Session\Contracts\FlashBagInterface;
use Concept\Core\Services\View\Contracts\ViewResponseFactoryInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SettingsController
{
    private const string MSG_SETTING_NOT_FOUND = 'Setting not found';
    private const string MSG_SETTING_CREATED = 'Setting successfully created';
    private const string MSG_SETTING_UPDATED = 'Setting successfully updated';
    private const string MSG_SETTING_DELETED = 'Setting successfully deleted';
    private const string MSG_INVALID_VALUE = 'Invalid setting value for the selected data type';

    private const string CONFIG_PAGINATION_PER_PAGE = 'pagination.per_page';
    private const int DEFAULT_PAGINATION_PER_PAGE = 15;

    private const string CONTEXT_SETTINGS = 'settings';
    private const string CONTEXT_SETTING = 'setting';
    private const string CONTEXT_SETTING_GROUPS = 'setting_groups';
    private const string CONTEXT_DATA_TYPES = 'data_types';
    private const string CONTEXT_ACTIVE_GROUP = 'active_group';
    private const string CONTEXT_DEFAULT_GROUP = 'default_group';
    private const string CONTEXT_FORM_VALUE = 'form_value';
    private const string CONTEXT_ID = 'id';

    public function __construct(
        private readonly ResponseFactoryInterface $response,
        private readonly ViewResponseFactoryInterface $viewResponse,
        private readonly ConfigInterface $config,
        private readonly FlashBagInterface $flashBag,
        private readonly SettingsManagerInterface $settings,
        private readonly SettingModel $settingModel,
        private readonly SettingFormValueMapper $formValueMapper,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $groupParam = $queryParams['group'] ?? null;
        $activeGroup = is_string($groupParam) && $groupParam !== ''
            ? SettingGroup::tryFrom($groupParam)?->value
            : null;

        $query = $this->settingModel
            ->newQuery()
            ->orderBy(SettingModel::FIELD_SETTING_GROUP)
            ->orderBy(SettingModel::FIELD_SETTING_KEY);

        if ($activeGroup !== null) {
            $query->where(SettingModel::FIELD_SETTING_GROUP, $activeGroup);
        }

        $settings = $query
            ->paginate($this->config->getInt(self::CONFIG_PAGINATION_PER_PAGE, self::DEFAULT_PAGINATION_PER_PAGE))
            ->withQueryString();

        return $this->viewResponse->create(ViewName::SETTINGS_LIST, [
            self::CONTEXT_SETTINGS => $settings,
            self::CONTEXT_SETTING_GROUPS => SettingGroup::cases(),
            self::CONTEXT_ACTIVE_GROUP => $activeGroup,
        ]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        return $this->viewResponse->create(
            ViewName::SETTINGS_CREATE,
            $this->formContext($this->resolveGroupFromQuery($request))
        );
    }

    public function store(StoreSettingRequest $request): ResponseInterface
    {
        /** @var StoreSettingDto $dto */
        $dto = $request->toDto();

        try {
            $value = $this->formValueMapper->fromForm($dto->setting_value, $dto->data_type);
        } catch (InvalidArgumentException) {
            $this->flashBag->addError(self::MSG_INVALID_VALUE);

            return $this->response->redirectByName(RouteName::SETTINGS_CREATE);
        }

        $this->settings->set(
            $dto->setting_key,
            $value,
            $dto->setting_group,
            $dto->data_type
        );

        $this->updateDescription($dto->setting_group, $dto->setting_key, $dto->description);
        $this->flashBag->addSuccess(self::MSG_SETTING_CREATED);

        return $this->response->redirectByName(RouteName::SETTINGS);
    }

    public function edit(int $id): ResponseInterface
    {
        $setting = $this->findSetting($id);

        if ($setting === null) {
            $this->flashBag->addError(self::MSG_SETTING_NOT_FOUND);

            return $this->response->redirectByName(RouteName::SETTINGS);
        }

        return $this->viewResponse->create(ViewName::SETTINGS_EDIT, array_merge(
            $this->formContext(),
            [
                self::CONTEXT_SETTING => $setting,
                self::CONTEXT_FORM_VALUE => $this->formValueMapper->toFormValue($setting),
            ]
        ));
    }

    public function update(UpdateSettingRequest $request, int $id): ResponseInterface
    {
        $setting = $this->findSetting($id);

        if ($setting === null) {
            $this->flashBag->addError(self::MSG_SETTING_NOT_FOUND);

            return $this->response->redirectByName(RouteName::SETTINGS);
        }

        /** @var UpdateSettingDto $dto */
        $dto = $request->toDto();

        try {
            $value = $this->formValueMapper->fromForm($dto->setting_value, $dto->data_type);
        } catch (InvalidArgumentException) {
            $this->flashBag->addError(self::MSG_INVALID_VALUE);

            return $this->response->redirectByName(RouteName::SETTINGS_EDIT, [self::CONTEXT_ID => $id]);
        }

        $this->settings->set(
            $dto->setting_key,
            $value,
            $dto->setting_group,
            $dto->data_type,
            $setting->getId()
        );

        $this->updateDescription($dto->setting_group, $dto->setting_key, $dto->description);
        $this->flashBag->addSuccess(self::MSG_SETTING_UPDATED);

        return $this->response->redirectByName(RouteName::SETTINGS);
    }

    public function destroy(int $id): ResponseInterface
    {
        $setting = $this->findSetting($id);

        if ($setting === null) {
            $this->flashBag->addError(self::MSG_SETTING_NOT_FOUND);

            return $this->response->redirectByName(RouteName::SETTINGS);
        }

        $this->settings->delete($setting->getSettingKey(), $setting->getSettingGroup());
        $this->flashBag->addSuccess(self::MSG_SETTING_DELETED);

        return $this->response->redirectByName(RouteName::SETTINGS);
    }

    /**
     * @return array<string, mixed>
     */
    private function formContext(?string $defaultGroup = null): array
    {
        return [
            self::CONTEXT_DATA_TYPES => SettingDataType::cases(),
            self::CONTEXT_SETTING_GROUPS => SettingGroup::cases(),
            self::CONTEXT_DEFAULT_GROUP => $defaultGroup ?? SettingGroup::GENERAL->value,
        ];
    }

    private function resolveGroupFromQuery(ServerRequestInterface $request): ?string
    {
        $groupParam = $request->getQueryParams()['group'] ?? null;

        if (!is_string($groupParam) || $groupParam === '') {
            return null;
        }

        return SettingGroup::tryFrom($groupParam)?->value;
    }

    private function findSetting(int $id): ?SettingModel
    {
        $setting = $this->settingModel
            ->newQuery()
            ->find($id);

        return $setting instanceof SettingModel ? $setting : null;
    }

    private function updateDescription(string $group, string $key, ?string $description): void
    {
        $this->settingModel
            ->newQuery()
            ->where(SettingModel::FIELD_SETTING_GROUP, $group)
            ->where(SettingModel::FIELD_SETTING_KEY, $key)
            ->update([
                SettingModel::FIELD_DESCRIPTION => FormValueNormalizer::nullableString($description),
                SettingModel::FIELD_UPDATED_AT => date('Y-m-d H:i:s'),
            ]);
    }
}
