/**
 * GetPagesInfoResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetPagesInfoResponse  implements java.io.Serializable {
    private boolean reversedReadingOrder;

    private java.math.BigInteger expectedPages;

    private java.lang.String pageOrderMethod;

    private com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages;

    private com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects;

    private com.woodwing.enterprise.interfaces.services.wfl.PlacedObject[] placedObjects;

    public GetPagesInfoResponse() {
    }

    public GetPagesInfoResponse(
           boolean reversedReadingOrder,
           java.math.BigInteger expectedPages,
           java.lang.String pageOrderMethod,
           com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages,
           com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects,
           com.woodwing.enterprise.interfaces.services.wfl.PlacedObject[] placedObjects) {
           this.reversedReadingOrder = reversedReadingOrder;
           this.expectedPages = expectedPages;
           this.pageOrderMethod = pageOrderMethod;
           this.editionsPages = editionsPages;
           this.layoutObjects = layoutObjects;
           this.placedObjects = placedObjects;
    }


    /**
     * Gets the reversedReadingOrder value for this GetPagesInfoResponse.
     * 
     * @return reversedReadingOrder
     */
    public boolean isReversedReadingOrder() {
        return reversedReadingOrder;
    }


    /**
     * Sets the reversedReadingOrder value for this GetPagesInfoResponse.
     * 
     * @param reversedReadingOrder
     */
    public void setReversedReadingOrder(boolean reversedReadingOrder) {
        this.reversedReadingOrder = reversedReadingOrder;
    }


    /**
     * Gets the expectedPages value for this GetPagesInfoResponse.
     * 
     * @return expectedPages
     */
    public java.math.BigInteger getExpectedPages() {
        return expectedPages;
    }


    /**
     * Sets the expectedPages value for this GetPagesInfoResponse.
     * 
     * @param expectedPages
     */
    public void setExpectedPages(java.math.BigInteger expectedPages) {
        this.expectedPages = expectedPages;
    }


    /**
     * Gets the pageOrderMethod value for this GetPagesInfoResponse.
     * 
     * @return pageOrderMethod
     */
    public java.lang.String getPageOrderMethod() {
        return pageOrderMethod;
    }


    /**
     * Sets the pageOrderMethod value for this GetPagesInfoResponse.
     * 
     * @param pageOrderMethod
     */
    public void setPageOrderMethod(java.lang.String pageOrderMethod) {
        this.pageOrderMethod = pageOrderMethod;
    }


    /**
     * Gets the editionsPages value for this GetPagesInfoResponse.
     * 
     * @return editionsPages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] getEditionsPages() {
        return editionsPages;
    }


    /**
     * Sets the editionsPages value for this GetPagesInfoResponse.
     * 
     * @param editionsPages
     */
    public void setEditionsPages(com.woodwing.enterprise.interfaces.services.wfl.EditionPages[] editionsPages) {
        this.editionsPages = editionsPages;
    }


    /**
     * Gets the layoutObjects value for this GetPagesInfoResponse.
     * 
     * @return layoutObjects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] getLayoutObjects() {
        return layoutObjects;
    }


    /**
     * Sets the layoutObjects value for this GetPagesInfoResponse.
     * 
     * @param layoutObjects
     */
    public void setLayoutObjects(com.woodwing.enterprise.interfaces.services.wfl.LayoutObject[] layoutObjects) {
        this.layoutObjects = layoutObjects;
    }


    /**
     * Gets the placedObjects value for this GetPagesInfoResponse.
     * 
     * @return placedObjects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PlacedObject[] getPlacedObjects() {
        return placedObjects;
    }


    /**
     * Sets the placedObjects value for this GetPagesInfoResponse.
     * 
     * @param placedObjects
     */
    public void setPlacedObjects(com.woodwing.enterprise.interfaces.services.wfl.PlacedObject[] placedObjects) {
        this.placedObjects = placedObjects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetPagesInfoResponse)) return false;
        GetPagesInfoResponse other = (GetPagesInfoResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            this.reversedReadingOrder == other.isReversedReadingOrder() &&
            ((this.expectedPages==null && other.getExpectedPages()==null) || 
             (this.expectedPages!=null &&
              this.expectedPages.equals(other.getExpectedPages()))) &&
            ((this.pageOrderMethod==null && other.getPageOrderMethod()==null) || 
             (this.pageOrderMethod!=null &&
              this.pageOrderMethod.equals(other.getPageOrderMethod()))) &&
            ((this.editionsPages==null && other.getEditionsPages()==null) || 
             (this.editionsPages!=null &&
              java.util.Arrays.equals(this.editionsPages, other.getEditionsPages()))) &&
            ((this.layoutObjects==null && other.getLayoutObjects()==null) || 
             (this.layoutObjects!=null &&
              java.util.Arrays.equals(this.layoutObjects, other.getLayoutObjects()))) &&
            ((this.placedObjects==null && other.getPlacedObjects()==null) || 
             (this.placedObjects!=null &&
              java.util.Arrays.equals(this.placedObjects, other.getPlacedObjects())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        _hashCode += (isReversedReadingOrder() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getExpectedPages() != null) {
            _hashCode += getExpectedPages().hashCode();
        }
        if (getPageOrderMethod() != null) {
            _hashCode += getPageOrderMethod().hashCode();
        }
        if (getEditionsPages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditionsPages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditionsPages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getLayoutObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getLayoutObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getLayoutObjects(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPlacedObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacedObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacedObjects(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetPagesInfoResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetPagesInfoResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("reversedReadingOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReversedReadingOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("expectedPages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExpectedPages"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrderMethod");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrderMethod"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editionsPages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionsPages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EditionPages"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "EditionPages"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "LayoutObject"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "LayoutObject"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placedObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PlacedObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PlacedObject"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PlacedObject"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
