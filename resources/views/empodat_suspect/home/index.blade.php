<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>


  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            EMPODAT Suspect Database
          </h1>

          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            EMPODAT Suspect Database is the central database for storing suspect screening occurrence results. Digital Sample Freezing Platform (DSFP)
            <a href="https://dsfp.norman-data.eu/" class="link-lime-text">https://dsfp.norman-data.eu/</a> is used for screening digitally archived high-resolution mass spectrometry (HRMS) datasets
            <a href="https://doi.org/10.1016/j.trac.2019.04.008" class="link-lime-text">https://doi.org/10.1016/j.trac.2019.04.008</a>.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Any amenable compound included in Substance Database (SusDat)
            <a href="https://norman-databases.org/susdat/substances/search/filter" class="link-lime-text">https://norman-databases.org/susdat/substances/search/filter</a> is searched retrospectively based on exact mass, predicted retention-time index window, isotopic pattern fit, and qualifier fragments.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Each identification is assigned an identification point (IP) score between 0 and 1, reflecting the strength of evidence in a transparent and reproducible way
            <a href="https://doi.org/10.1016/j.trac.2023.116944" class="link-lime-text">https://doi.org/10.1016/j.trac.2023.116944</a>.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            EMPODAT Suspect Database stores all resulting detections together with their metadata, enabling the environmental community and risk assessors to track the presence of newly identified contaminants across samples, locations, and years. It provides a searchable, structured, and continuously expanding evidence base that supports research, monitoring, and regulatory assessments.
          </p>

          <p class="text-gray-700 leading-relaxed">
            EMPODAT Suspect Database also provides essential input to the NORMAN prioritization framework for contaminants of emerging concern. Therefore, it serves as a bridge between non-target screening outputs and actionable environmental information.
          </p>

          <!-- Centered Image -->
          <div class="flex justify-center mt-6">
            <img src="{{ asset('images/databases/empodat_suspect.png') }}" alt="EMPODAT Suspect Database" class="w-1/2 h-auto">
          </div>

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
