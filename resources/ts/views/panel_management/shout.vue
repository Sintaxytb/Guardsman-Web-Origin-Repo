<script lang="ts">
import axios from "@axios";
import { requiredValidator } from "@validators";

export default defineComponent({
  data() {
    return {
      showToast: this.$showToast,
      requiredValidator,

      shout: ref<string>(''),
      submitting: ref<boolean>(false),
    }
  },

  methods: {
    submit() {
      const shoutForm: any = this.$refs.shoutForm;

      shoutForm?.validate().then(({valid: isValid} : any) => {
        if (isValid) {
          this.submitting = true;

          axios.put('shout', {
            shout: this.shout != "" ? this.shout : null
          })
          .then(response => {
            this.showToast("success", "Successfully updated the dashboard shout.")
          })
          .catch(response => {

          })
          .finally(() => {
            this.submitting = false;
          })
        }
      })
    }
  }
});

</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard title="Dashboard Shout">
        <VForm @submit.prevent="submit" ref="shoutForm">
          <VRow class="pa-6">
            <VCol cols="12">
              <VTextField
                v-model="shout"
                label="Dashboard Shout"
                prepend-inner-icon="tabler-speakerphone"
              />
            </VCol>

            <VCol
              cols="12"
            >
              <VBtn
                type="submit"
                class="me-2"
                :loading="submitting"
              >
                Update
              </VBtn>

              <VBtn
                color="secondary"
                type="reset"
                variant="tonal"
              >
                Reset
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VCol>
  </VRow>
</template>
