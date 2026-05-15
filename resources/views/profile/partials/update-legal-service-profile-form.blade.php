<section>
    @if ($person)
        <form method="post" action="{{ route('profile.legal-service.update') }}" class="space-y-6">
            @csrf
            @method('patch')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if ($person->type === 'individual')
                    <div>
                        <x-input-label for="legal_first_name" :value="__('First Name')" />
                        <x-text-input id="legal_first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $person->first_name)" required autocomplete="given-name" />
                        <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                    </div>

                    <div>
                        <x-input-label for="legal_middle_name" :value="__('Middle Name')" />
                        <x-text-input id="legal_middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $person->middle_name)" autocomplete="additional-name" />
                        <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
                    </div>

                    <div>
                        <x-input-label for="legal_last_name" :value="__('Last Name')" />
                        <x-text-input id="legal_last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $person->last_name)" required autocomplete="family-name" />
                        <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
                    </div>

                    <div>
                        <x-input-label for="legal_suffix" :value="__('Suffix')" />
                        <x-text-input id="legal_suffix" name="suffix" type="text" class="mt-1 block w-full" :value="old('suffix', $person->suffix)" maxlength="10" autocomplete="honorific-suffix" />
                        <x-input-error class="mt-2" :messages="$errors->get('suffix')" />
                    </div>
                @else
                    <div class="md:col-span-2">
                        <x-input-label for="legal_organization" :value="__('Organization')" />
                        <x-text-input id="legal_organization" name="organization" type="text" class="mt-1 block w-full" :value="old('organization', $person->organization)" required autocomplete="organization" />
                        <x-input-error class="mt-2" :messages="$errors->get('organization')" />
                    </div>
                @endif

                <div>
                    <x-input-label for="legal_title" :value="__('Title')" />
                    <x-text-input id="legal_title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $person->title)" autocomplete="organization-title" />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div>
                    <x-input-label for="legal_email" :value="__('Email')" />
                    <x-text-input id="legal_email" type="email" class="mt-1 block w-full bg-gray-100" :value="$person->email" readonly />
                    <p class="mt-2 text-xs text-gray-500">Email address changes are managed by the Hearing Unit administrator.</p>
                </div>

                <div>
                    <x-input-label for="legal_phone_mobile" :value="__('Mobile Phone')" />
                    <x-text-input id="legal_phone_mobile" name="phone_mobile" type="tel" class="mt-1 block w-full" :value="old('phone_mobile', $person->phone_mobile)" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone_mobile')" />
                </div>

                <div>
                    <x-input-label for="legal_phone_office" :value="__('Office Phone')" />
                    <x-text-input id="legal_phone_office" name="phone_office" type="tel" class="mt-1 block w-full" :value="old('phone_office', $person->phone_office)" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone_office')" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="legal_address_line1" :value="__('Address Line 1')" />
                    <x-text-input id="legal_address_line1" name="address_line1" type="text" class="mt-1 block w-full" :value="old('address_line1', $person->address_line1)" autocomplete="address-line1" />
                    <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="legal_address_line2" :value="__('Address Line 2')" />
                    <x-text-input id="legal_address_line2" name="address_line2" type="text" class="mt-1 block w-full" :value="old('address_line2', $person->address_line2)" autocomplete="address-line2" />
                    <x-input-error class="mt-2" :messages="$errors->get('address_line2')" />
                </div>

                <div>
                    <x-input-label for="legal_city" :value="__('City')" />
                    <x-text-input id="legal_city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $person->city)" autocomplete="address-level2" />
                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="legal_state" :value="__('State')" />
                        <x-text-input id="legal_state" name="state" type="text" class="mt-1 block w-full uppercase" :value="old('state', $person->state)" maxlength="2" autocomplete="address-level1" />
                        <x-input-error class="mt-2" :messages="$errors->get('state')" />
                    </div>

                    <div>
                        <x-input-label for="legal_zip" :value="__('ZIP Code')" />
                        <x-text-input id="legal_zip" name="zip" type="text" class="mt-1 block w-full" :value="old('zip', $person->zip)" autocomplete="postal-code" />
                        <x-input-error class="mt-2" :messages="$errors->get('zip')" />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save Legal Service Profile') }}</x-primary-button>

                @if (session('status') === 'legal-service-profile-updated')
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 3000)"
                        class="flex items-center px-3 py-2 bg-green-100 border border-green-200 text-green-700 rounded-md text-sm"
                    >
                        {{ __('Legal service profile updated and audited.') }}
                    </div>
                @endif
            </div>
        </form>
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            No legal service contact profile is linked to this account.
        </div>
    @endif
</section>
