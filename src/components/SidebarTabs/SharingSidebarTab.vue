<!--
  - @copyright Copyright (c) 2021 Jonas Rittershofer <jotoeri@users.noreply.github.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  - @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="sidebar-tabs__content">
		<SharingSearchDiv :current-shares="form.shares"
			:show-loading="isLoading"
			@add-share="addShare" />

		<!-- Internal link -->
		<div class="share-div">
			<div class="share-div__avatar icon-public" />
			<div class="share-div__desc share-div__desc--twoline">
				<span>{{ t('forms', 'Internal link') }}</span>
				<span>{{ t('forms', 'Only works for logged in users who can access this form') }}</span>
			</div>
			<Actions>
				<ActionButton icon="icon-clippy" @click="copyInternalShareLink($event, form.hash)">
					{{ t('forms', 'Copy to clipboard') }}
				</ActionButton>
			</Actions>
		</div>

		<!-- Public Link -->
		<div v-if="!hasPublicLink" class="share-div share-div--link">
			<div class="share-div__avatar icon-public-white" />
			<span class="share-div__desc">{{ t('forms', 'Public share link') }}</span>
			<Actions>
				<ActionButton icon="icon-add" @click="addPublicLink">
					{{ t('forms', 'Add public link') }}
				</ActionButton>
			</Actions>
		</div>
		<TransitionGroup v-else tag="div">
			<div v-for="share in publicLinkShares"
				:key="'share-' + share.shareType + '-' + share.shareWith"
				class="share-div share-div--link">
				<div class="share-div__avatar icon-public-white" />
				<span class="share-div__desc">{{ t('forms', 'Public Share Link') }}</span>
				<Actions>
					<ActionButton icon="icon-clippy" @click="copyPublicShareLink($event, share.shareWith)">
						{{ t('forms', 'Copy to clipboard') }}
					</ActionButton>
				</Actions>
				<Actions>
					<ActionButton icon="icon-delete" @click="removeShare(share)">
						{{ t('forms', 'Remove link') }}
					</ActionButton>
					<ActionButton :close-after-click="true" icon="icon-add" @click="addPublicLink">
						{{ t('forms', 'Add public link') }}
					</ActionButton>
				</Actions>
			</div>
		</TransitionGroup>

		<!-- Legacy Info, if present -->
		<div v-if="form.access.legacyLink" class="share-div">
			<div class="share-div__avatar icon-public" />
			<div class="share-div__desc share-div__desc--twoline">
				<span>{{ t('forms', 'Legacy Link') }}</span>
				<span>{{ t('forms', 'Form still supports old sharing-link.') }}</span>
			</div>
			<div v-tooltip="t('forms', 'For compatibility with the old Sharing, the internal link is still usable as public link. We recommend replacing the link with a new public link.')"
				class="share-div__legacy-warning icon-error-color" />
			<Actions>
				<ActionButton icon="icon-delete" @click="removeLegacyLink">
					{{ t('forms', 'Remove Legacy Link') }}
				</ActionButton>
			</Actions>
		</div>

		<!-- All users on Instance -->
		<div class="share-div">
			<div class="share-div__avatar icon-group" />
			<label for="share-switch__permit-all" class="share-div__desc">
				{{ t('forms', 'Permit access to all logged in users') }}
			</label>
			<CheckboxRadioSwitch id="share-switch__permit-all"
				:checked="form.access.permitAllUsers"
				type="switch"
				@update:checked="onPermitAllUsersChange" />
		</div>
		<div v-if="form.access.permitAllUsers" class="share-div share-div--indent">
			<div class="share-div__avatar icon-forms" />
			<label for="share-switch__show-to-all" class="share-div__desc">
				{{ t('forms', 'Show to all users on sidebar') }}
			</label>
			<CheckboxRadioSwitch id="share-switch__show-to-all"
				:checked="form.access.showToAllUsers"
				type="switch"
				@update:checked="onShowToAllUsersChange" />
		</div>

		<!-- Single shares -->
		<TransitionGroup tag="ul">
			<SharingShareDiv v-for="share in sortedShares"
				:key="'share-' + share.shareType + '-' + share.shareWith"
				:share="share"
				@remove-share="removeShare" />
		</TransitionGroup>
	</div>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import SharingSearchDiv from './SharingSearchDiv.vue'
import SharingShareDiv from './SharingShareDiv.vue'
import ShareTypes from '../../mixins/ShareTypes.js'
import ShareLinkMixin from '../../mixins/ShareLinkMixin.js'
import OcsResponse2Data from '../../utils/OcsResponse2Data.js'

export default {
	components: {
		Actions,
		ActionButton,
		CheckboxRadioSwitch,
		SharingSearchDiv,
		SharingShareDiv,
	},

	mixins: [ShareTypes, ShareLinkMixin],

	props: {
		form: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isLoading: false,
		}
	},

	computed: {
		sortedShares() {
			// Remove Link-Shares, which are handled separately, then sort
			return this.form.shares
				.filter(share => share.shareType !== this.SHARE_TYPES.SHARE_TYPE_LINK)
				.sort(this.sortByTypeAndDisplayname)
		},
		hasPublicLink() {
			return this.publicLinkShares.length !== 0
		},
		publicLinkShares() {
			return this.form.shares.filter(share => share.shareType === this.SHARE_TYPES.SHARE_TYPE_LINK)
		},
	},

	methods: {
		/**
		 * Add share
		 *
		 * @param {object} newShare the share object
		 */
		async addShare(newShare) {
			this.isLoading = true

			try {
				const response = await axios.post(generateOcsUrl('apps/forms/api/v2/share'), {
					formId: this.form.id,
					shareType: newShare.shareType,
					shareWith: newShare.shareWith,
				})
				const share = OcsResponse2Data(response)

				// Add new share
				this.$emit('add-share', share)

			} catch (error) {
				console.error(error)
				showError(t('forms', 'There was an error while adding the share'))
			} finally {
				this.isLoading = false
			}
		},

		async addPublicLink() {
			this.isLoading = true

			try {
				const response = await axios.post(generateOcsUrl('apps/forms/api/v2/share'), {
					formId: this.form.id,
					shareType: this.SHARE_TYPES.SHARE_TYPE_LINK,
				})
				const share = OcsResponse2Data(response)

				// Add new share
				this.$emit('add-share', share)

			} catch (error) {
				console.error(error)
				showError(t('forms', 'There was an error while adding the link'))
			} finally {
				this.isLoading = false
			}
		},

		/**
		 * Remove share
		 *
		 * @param {object} share the share to delete
		 */
		async removeShare(share) {
			this.isLoading = true

			try {
				await axios.delete(generateOcsUrl('apps/forms/api/v2/share/{id}', {
					id: share.id,
				}))
				this.$emit('remove-share', share)
			} catch (error) {
				console.error(error)
				showError(t('forms', 'There was an error while removing the share'))
			} finally {
				this.isLoading = false
			}
		},

		/**
		 * Sort by shareType and DisplayName
		 *
		 * @param {object} a first share for comparison
		 * @param {object} b second share for comparison
		 */
		sortByTypeAndDisplayname(a, b) {
			// First return, if ShareType does not match
			if (a.shareType < b.shareType) return -1
			if (a.shareType > b.shareType) return 1

			// Otherwise sort by displayname
			if (a.displayName.toLowerCase() < b.displayName.toLowerCase()) return -1
			if (a.displayName.toLowerCase() > b.displayName.toLowerCase()) return 1
			return 0
		},

		onPermitAllUsersChange(newVal) {
			const newAccess = { ...this.form.access }
			newAccess.permitAllUsers = newVal
			this.$emit('update:formProp', 'access', newAccess)
		},
		onShowToAllUsersChange(newVal) {
			const newAccess = { ...this.form.access }
			newAccess.showToAllUsers = newVal
			this.$emit('update:formProp', 'access', newAccess)
		},
		removeLegacyLink() {
			const newAccess = { ...this.form.access }
			delete newAccess.legacyLink
			this.$emit('update:formProp', 'access', newAccess)
		},
	},
}
</script>

<style lang="scss" scoped>
.share-div {
	display: flex;
	height: 44px;
	align-items: center;

	&--link {
		.share-div__avatar {
			background-color: var(--color-primary);
		}
	}

	&--indent {
		margin-left: 40px;
	}

	&__avatar {
		height: 32px;
		width: 32px;
		border-radius: 50%;
		background-color: var(--color-background-dark);
		background-size: 16px;
	}

	&__desc {
		padding: 8px;
		flex-grow: 1;

		&--twoline {
			span {
				display: block;
				height: 18px;
				line-height: 1.2em;
			}
			:last-child {
				color: var(--color-text-maxcontrast);
			}
		}
	}

	&__legacy-warning {
		background-size: 18px;
		margin-right: 4px;
	}
}
</style>
