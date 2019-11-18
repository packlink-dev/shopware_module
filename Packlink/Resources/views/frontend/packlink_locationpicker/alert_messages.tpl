{namespace name=frontend/packlink/dropoff}
{if $plIsDropoff && !$plIsSelected}
    <div class="alert is--warning is--rounded pl-spacer" id="pl-not-selected-dropoff-alert">

        <div class="alert--icon">
            <i class="icon--element icon--warning"></i>
        </div>

        <div class="alert--content">
            {s name="selectDropoffDescription"}This shipping service supports delivery to pre-defined drop-off locations. Please choose location that suits you the most by clicking on the "Select drop-off location" button.{/s}
        </div>

    </div>
{/if}