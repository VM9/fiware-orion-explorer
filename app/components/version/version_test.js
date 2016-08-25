'use strict';

describe('mainApp.version module', function() {
  beforeEach(module('mainApp.version'));

  describe('version service', function() {
    it('should return current version', inject(function(version) {
      expect(version).toEqual('0.1');
    }));
  });
});
